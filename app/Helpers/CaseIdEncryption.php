<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;

class CaseIdEncryption
{
    /**
     * Encrypt a case ID
     *
     * @param int $caseId
     * @return string
     */
    public static function encrypt($caseId)
    {
        return Crypt::encryptString($caseId);
    }

    /**
     * Decrypt a case ID
     *
     * @param string $encryptedId
     * @return int
     */
    public static function decrypt($encryptedId)
    {
        try {
            return (int) Crypt::decryptString($encryptedId);
        } catch (\Exception $e) {
            abort(404, 'Invalid case ID');
        }
    }

    /**
     * Generate encrypted route parameter for case ID
     *
     * @param int $caseId
     * @return string
     */
    public static function routeParam($caseId)
    {
        return self::encrypt($caseId);
    }
}
