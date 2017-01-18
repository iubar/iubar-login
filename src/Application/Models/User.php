<?php

namespace Application\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="User")
 * @ORM\Entity
 */
class User
{
    /**
     * @var string
     *
     * @ORM\Column(name="Email", type="string", length=64, nullable=true)
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="LastLogin", type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Active", type="boolean", nullable=true)
     */
    private $active;

    /**
     * @var string
     *
     * @ORM\Column(name="ActivationHash", type="string", length=40, nullable=true)
     */
    private $activationhash;

    /**
     * @var string
     *
     * @ORM\Column(name="RememberMeToken", type="string", length=64, nullable=true)
     */
    private $remembermetoken;

    /**
     * @var string
     *
     * @ORM\Column(name="PwdHash", type="string", length=255, nullable=true)
     */
    private $pwdhash;

    /**
     * @var string
     *
     * @ORM\Column(name="PwdResetHash", type="string", length=40, nullable=true)
     */
    private $pwdresethash;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="PwdResetTime", type="datetime", nullable=true)
     */
    private $pwdresettime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="FailedLogins", type="boolean", nullable=true)
     */
    private $failedlogins;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="LastFailedLogin", type="datetime", nullable=true)
     */
    private $lastfailedlogin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="CreationTime", type="datetime", nullable=true)
     */
    private $creationtime;

    /**
     * @var string
     *
     * @ORM\Column(name="CreationIp", type="string", length=39, nullable=true)
     */
    private $creationip;

    /**
     * @var string
     *
     * @ORM\Column(name="SessionId", type="string", length=48, nullable=true)
     */
    private $sessionid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Deleted", type="boolean", nullable=true)
     */
    private $deleted;

    /**
     * @var boolean
     *
     * @ORM\Column(name="AccountType", type="boolean", nullable=true)
     */
    private $accounttype;

    /**
     * @var boolean
     *
     * @ORM\Column(name="HasAvatar", type="boolean", nullable=true)
     */
    private $hasavatar;

    /**
     * @var string
     *
     * @ORM\Column(name="ProviderType", type="string", length=10, nullable=true)
     */
    private $providertype;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="SuspensionType", type="datetime", nullable=true)
     */
    private $suspensiontype;

    /**
     * @var string
     *
     * @ORM\Column(name="ApiKey", type="string", length=25, nullable=true)
     */
    private $apikey;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="SuspensionTime", type="datetime", nullable=true)
     */
    private $suspensiontime;

    /**
     * @var string
     *
     * @ORM\Column(name="Username", type="string", length=255)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $username;


    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set lastlogin
     *
     * @param \DateTime $lastlogin
     * @return User
     */
    public function setLastlogin($lastlogin)
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin
     *
     * @return \DateTime 
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return User
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set activationhash
     *
     * @param string $activationhash
     * @return User
     */
    public function setActivationhash($activationhash)
    {
        $this->activationhash = $activationhash;

        return $this;
    }

    /**
     * Get activationhash
     *
     * @return string 
     */
    public function getActivationhash()
    {
        return $this->activationhash;
    }

    /**
     * Set remembermetoken
     *
     * @param string $remembermetoken
     * @return User
     */
    public function setRemembermetoken($remembermetoken)
    {
        $this->remembermetoken = $remembermetoken;

        return $this;
    }

    /**
     * Get remembermetoken
     *
     * @return string 
     */
    public function getRemembermetoken()
    {
        return $this->remembermetoken;
    }

    /**
     * Set pwdhash
     *
     * @param string $pwdhash
     * @return User
     */
    public function setPwdhash($pwdhash)
    {
        $this->pwdhash = $pwdhash;

        return $this;
    }

    /**
     * Get pwdhash
     *
     * @return string 
     */
    public function getPwdhash()
    {
        return $this->pwdhash;
    }

    /**
     * Set pwdresethash
     *
     * @param string $pwdresethash
     * @return User
     */
    public function setPwdresethash($pwdresethash)
    {
        $this->pwdresethash = $pwdresethash;

        return $this;
    }

    /**
     * Get pwdresethash
     *
     * @return string 
     */
    public function getPwdresethash()
    {
        return $this->pwdresethash;
    }

    /**
     * Set pwdresettime
     *
     * @param \DateTime $pwdresettime
     * @return User
     */
    public function setPwdresettime($pwdresettime)
    {
        $this->pwdresettime = $pwdresettime;

        return $this;
    }

    /**
     * Get pwdresettime
     *
     * @return \DateTime 
     */
    public function getPwdresettime()
    {
        return $this->pwdresettime;
    }

    /**
     * Set failedlogins
     *
     * @param boolean $failedlogins
     * @return User
     */
    public function setFailedlogins($failedlogins)
    {
        $this->failedlogins = $failedlogins;

        return $this;
    }

    /**
     * Get failedlogins
     *
     * @return boolean 
     */
    public function getFailedlogins()
    {
        return $this->failedlogins;
    }

    /**
     * Set lastfailedlogin
     *
     * @param \DateTime $lastfailedlogin
     * @return User
     */
    public function setLastfailedlogin($lastfailedlogin)
    {
        $this->lastfailedlogin = $lastfailedlogin;

        return $this;
    }

    /**
     * Get lastfailedlogin
     *
     * @return \DateTime 
     */
    public function getLastfailedlogin()
    {
        return $this->lastfailedlogin;
    }

    /**
     * Set creationtime
     *
     * @param \DateTime $creationtime
     * @return User
     */
    public function setCreationtime($creationtime)
    {
        $this->creationtime = $creationtime;

        return $this;
    }

    /**
     * Get creationtime
     *
     * @return \DateTime 
     */
    public function getCreationtime()
    {
        return $this->creationtime;
    }

    /**
     * Set creationip
     *
     * @param string $creationip
     * @return User
     */
    public function setCreationip($creationip)
    {
        $this->creationip = $creationip;

        return $this;
    }

    /**
     * Get creationip
     *
     * @return string 
     */
    public function getCreationip()
    {
        return $this->creationip;
    }

    /**
     * Set sessionid
     *
     * @param string $sessionid
     * @return User
     */
    public function setSessionid($sessionid)
    {
        $this->sessionid = $sessionid;

        return $this;
    }

    /**
     * Get sessionid
     *
     * @return string 
     */
    public function getSessionid()
    {
        return $this->sessionid;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     * @return User
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean 
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set accounttype
     *
     * @param boolean $accounttype
     * @return User
     */
    public function setAccounttype($accounttype)
    {
        $this->accounttype = $accounttype;

        return $this;
    }

    /**
     * Get accounttype
     *
     * @return boolean 
     */
    public function getAccounttype()
    {
        return $this->accounttype;
    }

    /**
     * Set hasavatar
     *
     * @param boolean $hasavatar
     * @return User
     */
    public function setHasavatar($hasavatar)
    {
        $this->hasavatar = $hasavatar;

        return $this;
    }

    /**
     * Get hasavatar
     *
     * @return boolean 
     */
    public function getHasavatar()
    {
        return $this->hasavatar;
    }

    /**
     * Set providertype
     *
     * @param string $providertype
     * @return User
     */
    public function setProvidertype($providertype)
    {
        $this->providertype = $providertype;

        return $this;
    }

    /**
     * Get providertype
     *
     * @return string 
     */
    public function getProvidertype()
    {
        return $this->providertype;
    }

    /**
     * Set suspensiontype
     *
     * @param \DateTime $suspensiontype
     * @return User
     */
    public function setSuspensiontype($suspensiontype)
    {
        $this->suspensiontype = $suspensiontype;

        return $this;
    }

    /**
     * Get suspensiontype
     *
     * @return \DateTime 
     */
    public function getSuspensiontype()
    {
        return $this->suspensiontype;
    }

    /**
     * Set apikey
     *
     * @param string $apikey
     * @return User
     */
    public function setApikey($apikey)
    {
        $this->apikey = $apikey;

        return $this;
    }

    /**
     * Get apikey
     *
     * @return string 
     */
    public function getApikey()
    {
        return $this->apikey;
    }

    /**
     * Set suspensiontime
     *
     * @param \DateTime $suspensiontime
     * @return User
     */
    public function setSuspensiontime($suspensiontime)
    {
        $this->suspensiontime = $suspensiontime;

        return $this;
    }

    /**
     * Get suspensiontime
     *
     * @return \DateTime 
     */
    public function getSuspensiontime()
    {
        return $this->suspensiontime;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }
}
