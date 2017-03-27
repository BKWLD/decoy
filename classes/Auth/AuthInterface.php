<?php

namespace Bkwld\Decoy\Auth;

/**
 * Defines methods necessary to validate a user
 */
interface AuthInterface
{
    /**
     * Boolean as to whether the user has developer entitlements
     *
     * @return boolean
     */
    public function isDeveloper();

    /**
     * Avatar photo for the header
     *
     * @return string
     */
    public function getUserPhoto();

    /**
     * Name to display in the header for the user
     *
     * @return string
     */
    public function getShortName();

    /**
     * URL to the user's profile page in the admin
     *
     * @return string
     */
    public function getUserUrl();
}
