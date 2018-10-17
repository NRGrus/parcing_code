<?php
    $connection = new mysqli("localhost", "root", "", "tenderplus");

    if($connection === false) {
        die("ERROR: Could not connect. " . mysqli_connect_error());
    }

    if (!$connection->set_charset("utf8")) {
            printf("Ошибка при загрузке набора символов utf8: %s\n", $connection->error);
            exit();
        }

    ini_set('max_execution_time', 10800);
    ini_set('default_socket_timeout', 300);

    include_once('simple_html_dom.php');
    $main_html = new simple_html_dom();
    $url = "https://tenderplus.kz/organization?s%5BfullText%5D=&s%5Bregion_id%5D=3&page=";

    
    $main_html->load_file($url ."1");

    $pieceOfCode = strval($main_html->find('.company-stats')[0]);
    $numberOfCompany = 0;

    $id = 78720;

    for ($k = 0; $k < strlen($pieceOfCode); $k++) {
        if (is_numeric($pieceOfCode[$k]))
            $numberOfCompany = $numberOfCompany*10 + $pieceOfCode[$k];
    }

    $pageNumber = ceil($numberOfCompany/20);
    
    for ($i = 3937; $i <= $pageNumber; $i++) {
        
        $main_html->load_file($url . $i);

        foreach($main_html->find('.company-teaser') as $blocks) {
            $arr = array(
                'title' => '',
                'title_kaz' => '',
                'iin' => '',
                'OKPO' => '',
                'KATO' => '',
                'created_date' => '',
                'CEO' => '',
                'KOPF' => '',
                'KRP' => '',
                'ownership' => '',
                'legal_address' => '',
                'actual_address' => '',
                'OKED' => '',
            );
            $id++;
            $single_page_html = new simple_html_dom();
            
            
            $single_page_html->load_file($blocks->find('.link')[0]->href);

    
            $arr['title'] =  preg_replace('/\'/', '\\\'', $blocks->find('.link')[0]->innertext);

            $table = $single_page_html->find('table');
            $tb = 0;
            foreach ($table[0]->find('td') as $row) {
                if ($tb == 0)
                    $arr['iin'] = $row->innertext;
                elseif ($tb == 1)
                    $arr['OKPO'] = $row->innertext;
                elseif ($tb == 2)
                    $arr['KATO'] = $row->innertext;
                elseif ($tb == 3)
                    $arr['created_date'] = $row->innertext;
                $tb++;
            }
            for ($j = 1; $j < 3; $j++) {
                foreach ($table[$j]->find('tr') as $row) {
                    $td = $row->find('td');
                    if (strpos($td[0]->innertext, "Наименование") !== false)
                        $arr['title_kaz'] =  preg_replace('/\'/', '\\\'', $td[1]->innertext);
                    else if (strpos($td[0]->innertext, "Руководитель") !== false)
                        $arr['CEO'] = preg_replace('/\'/', '\\\'', $td[1]->innertext);
                    else if (strpos($td[0]->innertext, "КОПФ") !== false)
                        $arr['KOPF'] = $td[1]->innertext;
                    else if (strpos($td[0]->innertext, "Форма собственности") !== false)
                        $arr['ownership'] = $td[1]->innertext;
                    else if (strpos($td[0]->innertext, "КРП") !== false)
                        $arr['KRP'] = $td[1]->innertext;
                    else if (strpos($td[0]->innertext, "Юридический адрес") !== false) 
                        $arr['legal_address'] = preg_replace('/\'/', '\\\'', $td[1]->find('a')[0]->innertext);
                    else if (strpos($td[0]->innertext, "Фактический адрес") !== false)
                        $arr['actual_address'] =preg_replace('/\'/', '\\\'', $td[1]->find('a')[0]->innertext);
                    else if (strpos($td[0]->innertext, "ОКЭД") !== false)
                        $arr['OKED'] = $td[1]->innertext;
                }
            }

    
            $sql = "INSERT INTO counterparties (id, page, title, title_kaz, iin, OKPO, KATO, OKED, CEO, KOPF, KRP, ownership, legal_address, actual_address, region_id, created_date) 
            VALUES ('". $id ."', '". $i ."', '". $arr['title'] ."', '". $arr['title_kaz'] ."', '". $arr['iin'] ."', '". $arr['OKPO'] ."', '". $arr['KATO'] ."', '". $arr['OKED'] ."', '". $arr['CEO'] ."', '". $arr['KOPF'] ."', '". $arr['KRP'] ."', '". $arr['ownership'] ."', '". $arr['legal_address'] ."', '". $arr['actual_address'] ."', '3', '". $arr['created_date'] ."')";
            if(!mysqli_query($connection, $sql)){
                echo "ERROR: Could not able to execute  " . mysqli_error($connection);
            }
        }
        $single_page_html->clear();
        unset($single_page_html);
    }
    $main_html->clear();
    unset($main_html);

    mysqli_close();
?>