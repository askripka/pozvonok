<?php
ini_set('auto_detect_line_endings', 1);

class csvParser {

    /**
     * Detect CSV delimiter
     * ; or ,
     * @param $handle
     * @return string
     */
    public static function get_delimiter($handle) {
        rewind($handle);
        $data = fgets($handle, 1000);

        $delimiter1 = ';';
        $r = str_getcsv($data, $delimiter1);
        $delimiter1_result = count($r);

        $delimiter2 = ',';
        $r = str_getcsv($data, $delimiter2);
        $delimiter2_result = count($r);
        rewind($handle);

        return $delimiter1_result > $delimiter2_result ? $delimiter1 : $delimiter2;
    }

    /**
     * Распознаваемые комбинации:
     * мимя;+7131351513
     * +7131351513;мимя
     * мимя;7131351513
     * 7131351513;мимя
     * +7131351513
     * 7131351513
     */

    public function parse($file, $encoding = 'utf-8') {
        $handle = fopen($file, 'r');


        $str = fgets($handle, 1000);
        $str = fgets($handle, 1000);

        var_dump($str, mb_ereg_match('/\+?\d{10,14}/', trim($str)), preg_match('/\+?\d{10,14}/', trim($str)));
        exit;


        while ($data = fgetcsv($handle, 9000, $delimiter)) {
            $row++;
            $phone = null;
            $first_name = null;

            foreach ($data as $d) {


                if (mb_ereg_match('/^\+?\d{10,14}+$/', trim($d)) && !$phone) {
                    $phone = trim($d, '+');
                } elseif (mb_ereg_match('/^[A-Za-zА-Яа-яЁё]+$/', trim($d)) && !$first_name) {
                    $first_name = $d;
                } elseif ($phone && $first_name) {
                    break;
                }
            }


            echo $row.":".$phone.":".$first_name."<br>";
        }

        mb_regex_encoding('UTF-8');
        fclose($handle);


    }

}