<?php

class CsvImport
{

    static public function loadCsv($file, array $tweet, $hasHeader = true): array
    {
        $result = array();
        $handle = fopen($file, "r");

        //if first row has headers.. ignore
        if($hasHeader){
            $data = fgetcsv($handle);
        }
        //get the data into array
        while(($data = fgetcsv($handle)) !== false){
            $rawData[] = array($data);
        }
        $sampleSize = count($rawData);

        $r = 0;
        while($r < $sampleSize){

            $result[] = array('tweet' => self::getArray($rawData, $tweet, $r));         
            $r++;
        }
        
        return $result;
    }
    
    static public function getArray($rawData, $cols, $r): array
    {
        $arr = array();
        foreach($cols as $val){
            $arr[] = $rawData[$r][0][$val];
        }
        return $arr;
    }
}