<?php


namespace App\Services;

use App\Entity\User;

class UserAuthService
{
    /**
     * @param $user
     * @return bool
     */
    public function isSpecialist($user)
    {
        return $user instanceof User && $user->getRole() == 2;
    }

    /**
     * @param $user
     * @return bool
     */
    public function isPatient($user)
    {
        return $user instanceof User && $user->getRole() == 1;
    }

    /**
     * @param $user
     * @return bool
     */
    public function isClinic($user)
    {
        return $user instanceof User && $user->getRole() == 3;
    }
}
