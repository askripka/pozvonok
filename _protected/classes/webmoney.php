<?php



class WEBMONEY{

    public static function validate_sign($post, $secret_key) {
        $string =
            $post['LMI_PAYEE_PURSE'].
            $post['LMI_PAYMENT_AMOUNT'].
            $post['LMI_PAYMENT_NO'].
            $post['LMI_MODE'].
            $post['LMI_SYS_INVS_NO'].
            $post['LMI_SYS_TRANS_NO'].
            $post['LMI_SYS_TRANS_DATE'].
            $secret_key.
            $post['LMI_PAYER_PURSE'].
            $post['LMI_PAYER_WM'];

        $string = strtoupper(md5($string));
        return $string == $post['LMI_HASH'];
    }

}


