<?php

namespace Application\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Userexternal
 *
 * @ORM\Table(name="UserExternal")
 * @ORM\Entity
 */
class Userexternal
{
    /**
     * @var string
     *
     * @ORM\Column(name="Display", type="string", length=255, nullable=true)
     */
    private $display;

    /**
     * @var string
     *
     * @ORM\Column(name="FirstName", type="string", length=64, nullable=true)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="MiddleName", type="string", length=64, nullable=true)
     */
    private $middlename;

    /**
     * @var string
     *
     * @ORM\Column(name="Email", type="string", length=64, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="LastName", type="string", length=64, nullable=true)
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="PictureUrl", type="string", length=255, nullable=true)
     */
    private $pictureurl;

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
     * @ORM\Column(name="AccessToken", type="string", length=255, nullable=true)
     */
    private $accesstoken;

    /**
     * @var string
     *
     * @ORM\Column(name="AccessTokenScope", type="string", length=255, nullable=true)
     */
    private $accesstokenscope;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="AccessTokenExpireAt", type="datetime", nullable=true)
     */
    private $accesstokenexpireat;

    /**
     * @var string
     *
     * @ORM\Column(name="ProviderType", type="string", length=10, nullable=true)
     */
    private $providertype;

    /**
     * @var string
     *
     * @ORM\Column(name="Id", type="string", length=255)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     * Set display
     *
     * @param string $display
     * @return Userexternal
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return string 
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return Userexternal
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set middlename
     *
     * @param string $middlename
     * @return Userexternal
     */
    public function setMiddlename($middlename)
    {
        $this->middlename = $middlename;

        return $this;
    }

    /**
     * Get middlename
     *
     * @return string 
     */
    public function getMiddlename()
    {
        return $this->middlename;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Userexternal
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
     * Set lastname
     *
     * @param string $lastname
     * @return Userexternal
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set pictureurl
     *
     * @param string $pictureurl
     * @return Userexternal
     */
    public function setPictureurl($pictureurl)
    {
        $this->pictureurl = $pictureurl;

        return $this;
    }

    /**
     * Get pictureurl
     *
     * @return string 
     */
    public function getPictureurl()
    {
        return $this->pictureurl;
    }

    /**
     * Set creationtime
     *
     * @param \DateTime $creationtime
     * @return Userexternal
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
     * @return Userexternal
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
     * Set accesstoken
     *
     * @param string $accesstoken
     * @return Userexternal
     */
    public function setAccesstoken($accesstoken)
    {
        $this->accesstoken = $accesstoken;

        return $this;
    }

    /**
     * Get accesstoken
     *
     * @return string 
     */
    public function getAccesstoken()
    {
        return $this->accesstoken;
    }

    /**
     * Set accesstokenscope
     *
     * @param string $accesstokenscope
     * @return Userexternal
     */
    public function setAccesstokenscope($accesstokenscope)
    {
        $this->accesstokenscope = $accesstokenscope;

        return $this;
    }

    /**
     * Get accesstokenscope
     *
     * @return string 
     */
    public function getAccesstokenscope()
    {
        return $this->accesstokenscope;
    }

    /**
     * Set accesstokenexpireat
     *
     * @param \DateTime $accesstokenexpireat
     * @return Userexternal
     */
    public function setAccesstokenexpireat($accesstokenexpireat)
    {
        $this->accesstokenexpireat = $accesstokenexpireat;

        return $this;
    }

    /**
     * Get accesstokenexpireat
     *
     * @return \DateTime 
     */
    public function getAccesstokenexpireat()
    {
        return $this->accesstokenexpireat;
    }

    /**
     * Set providertype
     *
     * @param string $providertype
     * @return Userexternal
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
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }
}
