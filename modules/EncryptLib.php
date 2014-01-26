<?php
/**
 * Two way encryption
 */
class JC_EncryptLib{

	/**
     * Encrypt password using mcrypt
     *
     * Encrypt password using mcrypt if php module is installed, otherwise use base64 encode
     * 
     * @param string $pass plain string
     * @param string $key salt
     * @return string
     */
	public function encrypt($string, $key = ''){
		if(function_exists('mcrypt_encrypt')){

            if($key == '' || empty($key)){
                $key = AUTH_SALT;
            }
            return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
        }
        
        return base64_encode($string);
	}

	/**
     * Decrypt password using mcrypt
     *
     * Decrypt password using mcrypt if php module is installed, otherwise use base64 dencode
     * 
     * @param string $pass plain string
     * @param string $key salt
     * @return string
     */
	public function decrypt($string, $key = ''){
		if(function_exists('mcrypt_encrypt')){

            if($key == '' || empty($key)){
                $key = AUTH_SALT;
            }
            return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
        }
        
        return base64_decode($string);
	}
}
?>