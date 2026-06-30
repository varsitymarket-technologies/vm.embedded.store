<?php

/* 

TITLE: ENCRYPTION_SERVICES
VERSION: 1.0.2.0
AUTHOR: LEVIDOC
BUILD: VERIFIED

*/

class encryption_services
{
    public $encryption_keys;

    public function __construct($token_key)
    {
        $this->encryption_keys = ['threading' => $token_key,'silk'=>$token_key];
    }

    public function data_hash($input)
    {
        $output = hash('sha256', $input) ?? hash('md5', $input);
        return $output;
    }

    public function merge_bit_char($merge_char)
    {
        $merge_count = count($merge_char);
        $merge_index = -1;
        $merge_flag = false;
        $merge_data = "";
        while ($merge_flag == false) {
            $merge_index++;
            for ($i = 0; $i < $merge_count; $i++) {
                if ($merge_index >= strlen($merge_char[$i])) {
                    $merge_flag = true;
                    continue;
                    break;
                }
                $merge_data .= $merge_char[$i][$merge_index - 0];
            }
        }
        return $merge_data;
    }

    public function char_pos($char, $string)
    {
        $output = false;
        $data = str_split($string);
        $x = -1;
        foreach ($data as $e) {
            $x++;
            if ($e == $char) {
                $output = $x;

                if ($output !== false) {
                    break;
                }
            }
            # code...
        }
        return $output;
    }

    public function get_pattern()
    {
        return "0987654321~!@#$%^&*()_+{}\":<>?QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm| '-=[];,./\\";
    }

    public function encryption_threading($string, $mode = "sha256")
    {
        $string = $string . ' ';
        $pattern = $this->get_pattern();
        $output = null;
        $data_contents = '';
        $e_keys = $this->encryption_keys;
        $encryption_keys = $e_keys['threading'];
        $modes = ['md5', 'sha256', 'haval160,4'];

        if ($mode == "sha256") {
            $selected_mode = $modes[1];
        } else {
            $selected_mode = false;
            foreach ($modes as $e) {
                if ($mode == $e) {
                    $selected_mode = $e;
                }
            }
            if ($selected_mode == false) {
                exit('Improper Hash Algorithm');
                return false;
            }
        }

        $selected_mode = $modes[1];
        $e = str_split($string);
        $j = -1;
        foreach ($e as $set) {
            $j++;
            $salt_data_set = str_split($encryption_keys);
            $salt_value = $salt_data_set[$j] ?? $salt_data_set[round(($j) % (strlen($encryption_keys)), 0.0, PHP_ROUND_HALF_DOWN)];
            $salt_value = hash($selected_mode, $salt_value);
            $pepering_value = $salt_data_set[(strlen($encryption_keys) - 1 - $j)] ?? $salt_data_set[round((strlen($encryption_keys) - 1 - ($j) % (strlen($encryption_keys))), 0.0, PHP_ROUND_HALF_DOWN)];
            $pepering_value = hash($selected_mode, $pepering_value);
            $index_value = $this->char_pos($set, $pattern);
            $index_value = hash($selected_mode, $index_value);
            $original_value = hash($selected_mode, $set);
            $e_cell = $this->merge_bit_char([$salt_value, $index_value, $original_value, $pepering_value]);
            $data_contents .= $e_cell;
            $output = $data_contents;
        }
        return $output;
    }

    public function decryption_threading($data, $algorithm = "sha256")
    {
        $modes = [
            'sha256' => ['length' => 64],
            'md5' => ['length' => 32],
            'haval160,4' => ['length' => 40],
        ];

        if (isset($modes[$algorithm])) {
            $hash_algorithm = $algorithm;
            $hash_limit = $modes[$algorithm]['length'];
            $enc_string = $data;
        } else {
            exit('Selected Imprper Algorithm');
        }

        $output = "";
        $data = str_split($enc_string);
        $data_contents = [];
        $i = 4;
        $x = 0;
        $x_2 = 0;
        $x_3 = 0;
        $x_4 = 0;
        $construct_data = "";
        $construct_data2 = "";
        $construct_data3 = "";
        $construct_data4 = "";
        $salt_enc_data = [];
        $pepering_enc_data = [];
        $original_enc_data = [];
        $index_enc_data = [];
        foreach ($data as $e) {
            if ($i == 1) {
                $x++;
                if (($x > $hash_limit) || (strlen($construct_data) >= $hash_limit)) {
                    $data_contents[1] = $construct_data;
                    $salt_enc_data[] = $construct_data;
                    $x = 0;
                    $construct_data = $e;
                } else {
                    $construct_data .= $e;
                }
            } else if ($i == 2) {
                $x_2++;
                if (($x_2 > $hash_limit) || (strlen($construct_data2) >= $hash_limit)) {
                    $data_contents[2] = $construct_data2;
                    $original_enc_data[] = $construct_data2;
                    $x_2 = 0;
                    $construct_data2 = $e;
                } else {
                    $construct_data2 .= $e;
                }
            } else if ($i == 3) {
                $x_3++;
                if (($x_3 > $hash_limit) || (strlen($construct_data3) >= $hash_limit)) {
                    $index_enc_data[] = $construct_data3;
                    $data_contents[3] = $construct_data3;
                    $x_3 = 0;
                    $construct_data3 = $e;
                } else {
                    $construct_data3 .= $e;
                }
            } else if ($i >= 4) {
                $x_4++;
                if (($x_4 > $hash_limit) || (strlen($construct_data4) >= $hash_limit)) {
                    $data_contents[4] = $construct_data4;
                    $pepering_enc_data[] = $construct_data4;
                    $x_4 = 0;
                    $construct_data4 = $e;
                } else {
                    $construct_data4 .= $e;
                }
                $i = 0;
            }
            $i++;
            # code...
        }

        $flag = false;
        $k = -1;
        $pattern = $this->get_pattern();
        $encryption_guide = str_split($pattern);
        $encryption_map_guide = [];

        $data_set = [];
        $index_enc_data = $original_enc_data;
        while ($flag == false) {
            $k++;
            foreach ($encryption_guide as $s) {
                if ($index_enc_data[$k] == hash($hash_algorithm, $s)) {
                    $data_set[] = $s;
                }
            }

            if ($k >= (count($index_enc_data) - 1)) {
                $flag = true;
            }
        }

        foreach ($data_set as $e) {
            $output .= $e;
        }
        return $output;
        #Start The Decryption Process 

    }

    public function silk_encryption($data)
    {
        //Source From 
        // I was Lazy So I outsourced
        //Link https://www.geeksforgeeks.org/how-to-encrypt-and-decrypt-a-php-string/
    
        // Store the cipher method
        $ciphering = "AES-128-CTR";
        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        // Non-NULL Initialization Vector for encryption
        $encryption_iv = '1234567891011121';
        // Store the encryption key
        $encryption_key = $this->encryption_keys['silk'];
        // Use openssl_encrypt() function to encrypt the data
        $encryption = openssl_encrypt(
            $data,
            $ciphering,
            $encryption_key,
            $options,
            $encryption_iv
        );
        return $encryption; 
    }

    public function silk_decryption($data)
    {
        $decryption_iv = '1234567891011121';        
        // Store the cipher method
        $ciphering = "AES-128-CTR";
        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        // Store the decryption key
        $decryption_key = $this->encryption_keys['silk'];
        // Use openssl_decrypt() function to decrypt the data
        $decryption=openssl_decrypt ($data, $ciphering, 
                $decryption_key, $options, $decryption_iv);
        return $decryption; 
    }

    public function rays_decryption($data){
        // Store cipher method
        $ciphering = "BF-CBC";
        // Use OpenSSL encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        // Use random_bytes() function to generate a random initialization vector (iv)
        $encryption_iv = random_bytes($iv_length);
        // Alternatively, you can use a fixed iv if needed
        // $encryption_iv = openssl_random_pseudo_bytes($iv_length);    
        // Use php_uname() as the encryption key
        $encryption_key = openssl_digest(php_uname(), 'MD5', TRUE);
        // Decryption process
        $decryption = openssl_decrypt($data, $ciphering,$encryption_key, $options, $encryption_iv);
        return $decryption; 
    }

    public function rays_encryption($data){
        // Store cipher method
        $ciphering = "BF-CBC";
        // Use OpenSSL encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;

        // Use random_bytes() function to generate a random initialization vector (iv)
        $encryption_iv = random_bytes($iv_length);

        // Alternatively, you can use a fixed iv if needed
        // $encryption_iv = openssl_random_pseudo_bytes($iv_length);

        // Use php_uname() as the encryption key
        $encryption_key = openssl_digest(php_uname(), 'MD5', TRUE);

        // Encryption process
        $encryption = openssl_encrypt($data, $ciphering,$encryption_key, $options, $encryption_iv);

        return $encryption; 

    }


    function get_algorithms(){
        $output = [
            'rays_encryption'
                    =>[
                'encode'=>'rays_encryption',
                'decode'=>'rays_decryption'],
        ]; 

        return $output; 
    }
}
?>