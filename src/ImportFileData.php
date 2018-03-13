<?php
/**
 * User: Timkin Dmitriy
 * Date: 11.03.18
 * Time: 22:51
 */

namespace Walma\Aeromar;

use Yandex\Disk\DiskClient;
use \Bitrix\Highloadblock as HL;

class ImportFileData
{

    const TOKEN = 'AQAAAAAFY-xdAATcbWobDAXuKkguvxm5fcb_3QM';
    const PASSWD = 'a3ee8b05e8eb49ccbab657549de0b718';
    /**
     * @var array
     * Параметры, передаваемые через имя файла, разделенные "&"
     */
    private $_outParameters;
    private $_fileNameForImport;
    private $_currentDateTime;

    /**
     * @return string
     */
    public function getFileNameForImport()
    {
        return $this->_fileNameForImport;
    }

    /**
     * @param string $fileNameForImport
     */
    public function setFileNameForImport($fileNameForImport)
    {
        $this->_fileNameForImport = $fileNameForImport;
    }

    /**
     * @return array
     */
    public function getOutParameters()
    {
        return $this->_outParameters;
    }

    /**
     * @param mixed $outParameters
     */
    public function addOutParameters($outParameters)
    {
        $this->_outParameters[] = $outParameters;
    }

    /**
     * @param array $outParameters
     */
    public function setOutParameters(array $outParameters)
    {
        $this->_outParameters = $outParameters;
    }

    public function __construct()
    {
        $disk = new DiskClient();
        //Устанавливаем полученный токен
        $disk->setAccessToken(self::TOKEN);

        //Получаем список файлов из директории
        try {
            $files = $disk->directoryContents('/Aeromar/in/1c');
        } catch (ErrorException $error) {

        }
        $currentDateTime = new DateTime();
        $this->_currentDateTime = new DateTime();
        foreach ($files as $file) {
            if ($file['resourceType'] === 'dir' && $file['displayName'] === $currentDateTime->format('Y-m-d')) {
                echo "<pre>";
                print_r("ALERT!!!\nDirectory with `today`s name exist. Check for files to import\n");
                print_r($file);
                echo "</pre>";
                $filesInDay = $disk->directoryContents($file['href']);
                foreach ($filesInDay as $item) {
                    if ($item['resourceType'] === 'file') {
                            $fileDate = substr($item['displayName'], 0, 10);
                            if ($fileDate !== $currentDateTime->format('Y-m-d')) { // левый файл, не подходящий по дате пропустим
                                continue;
                            }
                            $fileTime = str_replace('-', ':', substr($item['displayName'], 11, 8));
                            echo "<pre>";
                            print_r($fileDate."\t" . $fileTime);
                            echo "</pre>";
                            $fileDateTime = new DateTime($fileDate . ' ' . $fileTime);
                        $dd = $currentDateTime->sub(new DateInterval('PT1H'));
                        $diff = $fileDateTime->diff($dd);

                        if ($diff->h < 1 && $diff->i <= 59) {
                            $this->_fileNameForImport = $item['href'];
                            $this->_fileNameForImport = $disk->downloadFile($item['href'], '/tmp/', $item['displayName']);
                            echo "<pre>";
                            print_r("File to import FOUND: " . $this->_fileNameForImport . "\nStart import\n");
                            echo "</pre>";
                            $data = $this->_parseFile();

                            $this->importDataToDb($data);
                        }
                        if (strpos($item['displayName'], '&') !== false) {
                            $fileDateTime = explode('&', $item['displayName']);
                            foreach ($fileDateTime as $index => $params) {
                                if ($index === 0) {
                                    continue;
                                }

                                preg_match_all("/(.+)=(.+)/", $params, $parameters);
                                unset($parameters[0]);
                                $this->addOutParameters(array_combine($parameters[1], $parameters[2]));
                            }
                        }
                    }

                }
            }
        }
    }

    private function _parseFile()
    {
        if (empty($this->_fileNameForImport)) {
            return false;
        }
        $xml = simplexml_load_file($this->_fileNameForImport);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        return $array;

    }

    public function importDataToDb($data)
    {
//        require_once $_SERVER['DOCUMENT_ROOT'] . '/ImportDataHl.php';
        $hl           = HL\HighloadBlockTable::getList(array('filter' => array('NAME' => 'ImportData')))->fetch();
        $entity       = HL\HighloadBlockTable::compileEntity( $hl ); //генерация класса
        $entityClass  = $entity->getDataClass();
        $query = new \Bitrix\Main\Entity\Query($entity);

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'FlightInfo':
                    $flightNum = $value['FlightNumber'];
                    $flightDateTime = $value['FlightDate'] . ' ' . $value['FlightTime'];
                    break;
                case 'Documents':
                    foreach ($value as $i => $document) {
                        if (count($document['PreOrder']) > 1) {
                            $documents[$i]['preOrder'] = json_encode($document['PreOrder']);
                        }
                        $documents[$i]['awbNum'] = $document['AwbNum'];
                        foreach ($document['DocumentLines'] as $documentLine) {
                            if (array_key_exists('0', $documentLine)) {
                                foreach ($documentLine as $line) {
                                    $documents[$i]['lines'][] = $line;
                                }
                            } else {
                                $documents[$i]['lines'][] = $documentLine;
                            }
                        }
                    }
                    break;
                case 'DocumentNumber':
                    $documentNumber = $value;
                    break;
                case 'Currency':
                    break;
            }
        }
        foreach ($documents as $index => $document) {
            foreach ($document['lines'] as $line) {
                $findedLine = $entityClass::getList(array(
                    'select' => array('ID', 'UF_FLIGHT_NUM', 'UF_ACCOUNT_ID', 'UF_DOCUMENT_NUM', 'UF_AWB_NUM', 'UF_MATERIALNO', 'UF_MATERIALNAME'),
                    'filter' => array(
                        '=UF_ACCOUNT_ID' => 1,
                        '=UF_FLIGHT_NUM' => $flightNum,
                        '=UF_AWB_NUM' => $document['awbNum'],
                        '=UF_DOCUMENT_NUM' => $documentNumber,
                        '=UF_MATERIALNO' => $line['MaterialNo'],
                    )
                ))->fetch();
                if (!$findedLine) {
                    $res = $entityClass::add(array(
                        'UF_ACCOUNT_ID' => 1,
                        'UF_DOCUMENT_NUM' => trim($documentNumber),
                        'UF_FLIGHT_NUM' => trim($flightNum),
                        'UF_FLIGHT_DATETIME' => trim($flightDateTime),
                        'UF_AWB_NUM' => trim($document['awbNum']),
                        'UF_CATEGORY' => trim($line['Category']),
                        'UF_GROUP_NAME' => trim($line['GroupName']),
                        'UF_TRAYNO' => trim($line['TrayNo']),
                        'UF_MATERIALNO' => trim($line['MaterialNo']),
                        'UF_MATERIALNAME' => trim($line['MaterialName']),
                        'UF_QUANTITY' => trim($line['Quantity']),
                        'UF_UOM' => trim($line['UOM']),
                        'UF_PRICE' => trim($line['Price']),
                        'UF_CURRENCY' => trim($line['Currency']),
                        'UF_PREORDER' => $document['preOrder'],
                    ));
                    if (!$res->isSuccess()) {
                        $errors = $res->getErrorMessages();
                        throw new \Exception("Errors add document line: " . $errors);
                    }
                }

            }
        }

    }
}
