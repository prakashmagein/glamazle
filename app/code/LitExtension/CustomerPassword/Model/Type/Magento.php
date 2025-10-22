<?php
/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

namespace LitExtension\CustomerPassword\Model\Type;

class Magento extends \LitExtension\CustomerPassword\Model\Type {

    public function validatePassword($customerModel, $email, $string, $encrypted) {
        $hashArr = explode(':', $encrypted);
        switch (count($hashArr)) {
            case 1:
                return $this->hash($string) === $encrypted;
            case 2:
                return $this->hash($hashArr[1] . $string) === $hashArr[0] || $this->hash256($hashArr[1] . $string) === $hashArr[0];
        }
        return false;
    }
    
    public function hash($data)
    {
        return md5($data);
    }
	
	public function hash256($data)
	{
        return hash('sha256', $data);
    }

}
