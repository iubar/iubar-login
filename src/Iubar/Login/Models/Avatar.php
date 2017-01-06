<?php

namespace Iubar\Login\Models;

use Iubar\Login\Core\DbResource;
use Iubar\Login\Models\User;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Text;

class Avatar extends AbstractLogin {
    /**
     * Gets a gravatar image link from given email address
     *
     * Gravatar is the #1 (free) provider for email address based global avatar hosting.
     * The URL (or image) returns always a .jpg file ! For deeper info on the different parameter possibilities:
     * @see http://gravatar.com/site/implement/images/
     * @source http://gravatar.com/site/implement/images/php/
     *
     * This method will return something like http://www.gravatar.com/avatar/79e2e5b48aec07710c08d50?s=80&d=mm&r=g
     * Note: the url does NOT have something like ".jpg" ! It works without.
     *
     * Set the configs inside the application/config/ files.
     *
     * @param string $email The email address
     * @return string
     */
    public static function getGravatarLinkByEmail($email)
    {
        return 'http://www.gravatar.com/avatar/' .
        md5(strtolower(trim($email))) .
        '?s=' . self::config('auth.avatar.size') . '&d=' . self::config('auth.gravatar.imageset') . '&r=' . self::config('auth.gravatar.rating');
    }
    /**
     * Gets the user's avatar file path
     * @param int $user_has_avatar Marker from database
     * @param string $user_name
     * @return string Avatar file path
     */
    public static function getPublicAvatarFilePathOfUser($user_has_avatar, $user_name){
        if ($user_has_avatar) {        	
            return self::config('app.baseurl') . self::config('auth.avatar.path.public') . self::getIdForImage($user_name) . '.jpg';
        }
        return self::config('app.baseurl') . self::config('auth.avatar.path.public') . self::config('auth.avatar.default');
    }
    /**
     * Gets the user's avatar file path
     * @param string $userName
     * @return string avatar picture path
     */
    public static function getPublicUserAvatarFilePathByUserName($userName){
    	$user = User::getByUsername($userName);
        if ($user && $user->getHasavatar()) {
            return self::config('app.baseurl') . self::config('auth.avatar.path.public') . self::getIdForImage($user_name) . '.jpg';
        }
        return self::config('app.baseurl') . self::config('auth.avatar.path.public') . self::config('auth.avatar.default');
    }
    /**
     * Create an avatar picture (and checks all necessary things too)
     * TODO decouple
     * TODO total rebuild
     */
    public static function createAvatar(){
        // check avatar folder writing rights, check if upload fits all rules
        if (self::isAvatarFolderWritable() AND self::validateImageFile()) {
            // create a jpg file in the avatar folder, write marker to database
            $user_name = Session::get(Session::SESSION_USER_NAME);
            $target_file_path = self::config('auth.avatar.path') . self::getIdForImage($user_name);
            self::resizeAvatarImage($_FILES['avatar_file']['tmp_name'], $target_file_path, self::config('auth.avatar.size'), self::config('auth.avatar.size'));
            self::writeAvatarToDatabase(Session::getDecoded(Session::SESSION_USER_NAME));
            Session::set(Session::SESSION_USER_AVATAR_FILE, self::getPublicUserAvatarFilePathByUserName(Session::get(Session::SESSION_USER_NAME)));
            Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_AVATAR_UPLOAD_SUCCESSFUL'));
        }
    }
    /**
     * Checks if the avatar folder exists and is writable
     *
     * @return bool success status
     */
    public static function isAvatarFolderWritable(){
        if (is_dir(self::config('auth.avatar.path')) AND is_writable(self::config('auth.avatar.path'))) {
            return true;
        }
        Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_AVATAR_FOLDER_DOES_NOT_EXIST_OR_NOT_WRITABLE'));
        return false;
    }
    /**
     * Validates the image
     * Only accepts gif, jpg, png types
     * @see http://php.net/manual/en/function.image-type-to-mime-type.php
     *
     * @return bool
     */
    public static function validateImageFile(){
        if (!isset($_FILES['avatar_file'])) {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_AVATAR_IMAGE_UPLOAD_FAILED'));
            return false;
        }
        // if input file too big (>5MB)
        if ($_FILES['avatar_file']['size'] > 5000000) {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_AVATAR_UPLOAD_TOO_BIG'));
            return false;
        }
        // get the image width, height and mime type
        $image_proportions = getimagesize($_FILES['avatar_file']['tmp_name']);
        // if input file too small, [0] is the width, [1] is the height
        if ($image_proportions[0] < self::config('auth.avatar.size') OR $image_proportions[1] < self::config('auth.avatar.size')) {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_AVATAR_UPLOAD_TOO_SMALL'));
            return false;
        }
        // if file type is not jpg, gif or png
        if (!in_array($image_proportions['mime'], array('image/jpeg', 'image/gif', 'image/png'))) {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_AVATAR_UPLOAD_WRONG_TYPE'));
            return false;
        }
        return true;
    }
    /**
     * Writes marker to database, saying user has an avatar now
     *
     * @param string $user_name
     */
    public static function writeAvatarToDatabase($user_name){ 
	    $dql = "UPDATE " . User::TABLE_NAME . " u SET u.hasavatar = 1 WHERE u.username = '" . $user_name . "'"; 
	    $numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
	    return $numUpdated;
    }
    /**
     * Resize avatar image (while keeping aspect ratio and cropping it off in a clean way).
     * Only works with gif, jpg and png file types. If you want to change this also have a look into
     * method validateImageFile() inside this model.
     *
     * TROUBLESHOOTING: You don't see the new image ? Press F5 or CTRL-F5 to refresh browser cache.
     *
     * @param string $source_image The location to the original raw image
     * @param string $destination The location to save the new image
     * @param int $final_width The desired width of the new image
     * @param int $final_height The desired height of the new image
     *
     * @return bool success state
     */
    public static function resizeAvatarImage($source_image, $destination, $final_width = 44, $final_height = 44){
        $imageData = getimagesize($source_image);
        $width = $imageData[0];
        $height = $imageData[1];
        $mimeType = $imageData['mime'];
        if (!$width || !$height) {
            return false;
        }
        switch ($mimeType) {
            case 'image/jpeg': $myImage = imagecreatefromjpeg($source_image); break;
            case 'image/png': $myImage = imagecreatefrompng($source_image); break;
            case 'image/gif': $myImage = imagecreatefromgif($source_image); break;
            default: return false;
        }
        // calculating the part of the image to use for thumbnail
        if ($width > $height) {
            $verticalCoordinateOfSource = 0;
            $horizontalCoordinateOfSource = ($width - $height) / 2;
            $smallestSide = $height;
        } else {
            $horizontalCoordinateOfSource = 0;
            $verticalCoordinateOfSource = ($height - $width) / 2;
            $smallestSide = $width;
        }
        // copying the part into thumbnail, maybe edit this for square avatars
        $thumb = imagecreatetruecolor($final_width, $final_height);
        imagecopyresampled($thumb, $myImage, 0, 0, $horizontalCoordinateOfSource, $verticalCoordinateOfSource, $final_width, $final_height, $smallestSide, $smallestSide);
        // add '.jpg' to file path, save it as a .jpg file with our $destination_filename parameter
        imagejpeg($thumb, $destination . '.jpg', self::config('auth.avatar.quality'));
        imagedestroy($thumb);
        if (file_exists($destination)) {
            return true;
        }
        return false;
    }
    /**
     * Delete a user's avatar
     *
     * @param int $userName
     * @return bool success
     */
    public static function deleteAvatar($userName){
        if (!$userName) { //TODO: aggiungere altri eventuali controlli
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_FAILED"));
            return false;
        }
        // try to delete image, but still go on regardless of file deletion result
        self::deleteAvatarImageFile($userName);
        
        $dql = "UPDATE " . User::TABLE_NAME . " u SET u.hasavatar = 0 WHERE u.username = '" . $userName . "'";
        $numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();

        if ($numUpdated == 1) {
            Session::set(Session::SESSION_USER_AVATAR_FILE, self::getPublicUserAvatarFilePathByUserName($userName));
            Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_SUCCESSFUL"));
            return true;
        } else {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_FAILED"));
            return false;
        }
    }
    /**
     * Removes the avatar image file from the filesystem
     *
     * @param string $userName
     * @return bool
     */
    public static function deleteAvatarImageFile($userName){
    	
    	$avatarId = self::getIdForImage($userName);
    	
        // Check if file exists
        if (!file_exists(self::config('auth.avatar.path') . $avatarId . ".jpg")) {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_NO_FILE"));
            return false;
        }
        // Delete avatar file
        if (!unlink(self::config('auth.avatar.path') . $avatarId . ".jpg")) {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_FAILED"));
            return false;
        }
        return true;
    }
    
    private static function getIdForImage($user_name) {
    	$id = preg_replace('/\s+/', '', $user_name); // rimuovo eventuali spazi dallo username
    	return $id;
    }
}