<?php
/**
 * User: Timkin Dmitriy
 * Date: 11.03.18
 * Time: 22:51
 */

namespace Walma\Aeromar;

use Yandex\Disk\DiskClient;
use Yandex\Disk\Exception\DiskRequestException;
use \Bitrix\Highloadblock as HL;
use \Rollbar\Rollbar;
use \Rollbar\Payload\Level;

class ImportFileData
{

    const TOKEN = 'AQAAAAAFY-xdAATcbWobDAXuKkguvxm5fcb_3QM';
    const PASSWD = 'a3ee8b05e8eb49ccbab657549de0b718';

    const INITIAL_PATH                          = "/Aeromar/in/1c";
    const INITIAL_PATH_NOT_FOUND                = "Не найден начальный путь";
    const FOLDER_CURRENT_DATE_NOT_FOUND         = "Не найдена папка с текущей датой в наименовании";
    const IMPORT_FILE_NOT_FOUND                 = "Не найден файл для импорта";
    const FREE_ACCOUNT_NOT_FOUND                = "Свободные аккаунты отсутствуют";
    const CANT_SET_ACCOUNT_TO_BUSY              = "Ошибка занятия аккаунта рейсом";
    const INCLUDE_FOLDER_ATTENTION              = "Вложенная папка";
    const WRONG_FILENAME_FORMAT                 = "Неверный формат наименования файла, формат должен быть: YYYY-MM-DD-HH-II-SS&param1=value1.xml";
    const FILENAME_DATA_MISMATCH_CURRENT_FOLDER = "Дата в наименовании файла не соответствует текущей папке";
    const EMPTY_FILENAME                        = "Пустое имя файла для парсинга";
    const ERROR_LOADING_XML                     = "Ошибка загрузки XML";
    const ERROR_LINK_REGISTRATION_CODE          = "Ошибка привязки регистрационного кода. Один из аккаунтов уже привязан";

    /**
     * @var array
     * Параметры, передаваемые через имя файла, разделенные "&"
     */
    private $_outParameters;
    private $_fileNameHandleForImport;
    private $_currentDateTime;

    /**
     * @return string
     */
    public function getFileNameHandleForImport()
    {
        return $this->_fileNameHandleForImport;
    }

    /**
     * @param string $fileNameForImport
     */
    public function setFileNameHandleForImport($fileNameForImport)
    {
        $this->_fileNameHandleForImport = $fileNameForImport;
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

    public function __construct($token = null)
    {
        $disk = new DiskClient();
        //Устанавливаем полученный токен
        $disk->setAccessToken(empty($token) ? self::TOKEN : $token);

        // Получаем список файлов из начальной папки
        try {
            $files = $disk->directoryContents(self::INITIAL_PATH);
        } catch (DiskRequestException $error) {
            Rollbar::log(Level::ERROR, self::INITIAL_PATH_NOT_FOUND .  "(" . self::INITIAL_PATH . ")");
            return self::INITIAL_PATH_NOT_FOUND .  "(" . self::INITIAL_PATH . ")";
        }
        $currentDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        // сохраним текущее время, время запуска скрипта проверки папки. в дальнейшем будем сравнивать с ним
        $this->_currentDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $flagFoundFolderWithCurrentDate = false;
        foreach ($files as $file) {
            // ищем папку с текущей датой
            // @todo либо если до полуночи меньше часа, то ищем и папку с завтрашним днем.
            // если до полуночи меньше часа и папку с завтрашним днем не нашли, то генерим ошибку
            // Если папку с текущим днем не нашли, то пише в Rollbar
            if ($file['resourceType'] === 'dir' && $file['displayName'] === $currentDateTime->format('Y-m-d')) {
                $flagFoundFolderWithCurrentDate = true;
                // нашли папку с текущей датой
                // получаем контент искомой папки
                try {
                    $itemsInDay = $disk->directoryContents($file['href']);
                } catch (DiskRequestException $diskRequestException) {
                    Rollbar::log(Level::ERROR, $diskRequestException->getMessage());
                    return $diskRequestException->getMessage();
                }
                // перебираем файлы
                $flagFoundFileWithContentToImport = false;
                foreach ($itemsInDay as $item) {
                    if ($item['resourceType'] === 'file') {
                        $filename = $item['displayName'];
                        if (!preg_match("/(\d{4}(-\d{2}){5})(&\w+=\S+)+(\.+)/", $filename)) {
                            Rollbar::log(Level::INFO, self::WRONG_FILENAME_FORMAT . ". файл: " . $item['displayName']);
                            continue;
                        }
                        $fileDate = substr($item['displayName'], 0, 10);
                        if ($fileDate !== $currentDateTime->format('Y-m-d')) { // левый файл, не подходящий по дате пропустим
                            Rollbar::log(Level::INFO, self::FILENAME_DATA_MISMATCH_CURRENT_FOLDER . ". файл: " . $item['displayName']);
                            continue;
                        }

                        // формат файла: (\d+{4})(-\d+{2}){5}&((\w+)=(\S+))+\.xml
                        // 2017-09-10-00-05-00&flight=ЮТ285.xml
                        $fileTime = str_replace('-', ':', substr($item['displayName'], 11, 8));
                        $fileDateTime = new \DateTime($fileDate . ' ' . $fileTime, new \DateTimeZone('UTC'));
                        // добавим час и будем смотреть
                        $dd = $currentDateTime->add(new \DateInterval('PT1H'));
//                        $diff = $fileDateTime->diff($dd);

                        $fileObj = new Files();
//                        echo "<pre>";
//                        print_r("Текущее UTC время: " . $this->_currentDateTime->format('Y-m-d H:i:s') . "\n");
//                        print_r("UTC время файла: " . $fileDateTime->format('Y-m-d H:i:s') . "\n");
//                        print_r($item['displayName']);
//                        echo "</pre>";
                        if (
                            $dd >= $fileDateTime
                            && $fileDateTime > $this->_currentDateTime
                            && !$fileObj->isExist($item['displayName'])
                            && $fileObj
                        ) {
                            $flagFoundFileWithContentToImport = true;
//                            $this->_fileNameForImport = $item['href'];
                            try {
                                $this->_fileNameHandleForImport = $disk->downloadFile($item['href'], '/tmp/', $item['displayName']);
                            } catch (DiskRequestException $diskRequestException) {
                                Rollbar::log(Level::ERROR, $diskRequestException->getMessage());
                                continue;
                            }
                            if (empty($this->_fileNameHandleForImport)) {
                                Rollbar::log(Level::ERROR, self::EMPTY_FILENAME);
                                return self::EMPTY_FILENAME;
                            }
                            // запишем в БД какой файл начинаем загружать
                            $addedFile = new Files();
                            $newId = $addedFile->add(['filename' => $item['displayName']]);

                            // читаем загруженный файл
                            $data = $this->_parseFile();
                            if (is_string($data)) { // строка с ошибками, иначе вернется массив
                                Rollbar::log(Level::ERROR, print_r($data, true));
                                return $data;
                            }
                            // импортируем в HL
                            // для этого найдем первый свободный от рейса аккаунт
                            $freeAccount = new Account();
                            $accountId = $freeAccount->findFreeAccount();
                            if ($accountId === false) {
                                Rollbar::log(Level::ERROR, self::FREE_ACCOUNT_NOT_FOUND);
                                return self::FREE_ACCOUNT_NOT_FOUND;
                            }
//                            @todo отключим на время тестов
                            $res = $this->importDataToDb($data, $accountId);
                            if (
                                self::CANT_SET_ACCOUNT_TO_BUSY === $res
                                || self::ERROR_LINK_REGISTRATION_CODE === $res
                            ) {
                                // ошибка занятия аккаунта
                                return $res;
                            }
                            // отметим загруженный файл
                            if (!empty($newId)) {
                                $completedFile = new Files();
                                $completedFile->setFileCompleted($newId);
                            }
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
                    } else { // вложенных папок не должно быть
                        // запишем в роллбар инфу
                        Rollbar::log(Level::INFO, "В папке [" . $file['displayName'] . "] обнаружилась " . self::INCLUDE_FOLDER_ATTENTION . "(" . $item['displayName'] . ")");
                    }

                }
                if (!$flagFoundFileWithContentToImport) {
                    Rollbar::log(Level::ERROR, " В папке [" . $file['displayName'] . "] " . self::IMPORT_FILE_NOT_FOUND);
                    return " В папке [" . $file['displayName'] . "] " . self::IMPORT_FILE_NOT_FOUND;
                }
            }
        }
        if (!$flagFoundFolderWithCurrentDate) { // значит не нашли папку с текущей датой
            Rollbar::log(Level::ERROR, self::FOLDER_CURRENT_DATE_NOT_FOUND);
            return self::FOLDER_CURRENT_DATE_NOT_FOUND;
        }
    }

    private function _parseFile()
    {
        libxml_use_internal_errors(true);
        if (false !== $xml = simplexml_load_file($this->_fileNameHandleForImport)) {
            $json = json_encode($xml);
            $array = json_decode($json,TRUE);
            return $array;
        } else {
            $errorMessages[] = self::ERROR_LOADING_XML;
            foreach(libxml_get_errors() as $error) {
                $errorMessages[] = $error->message;
            }
            return implode("\n", $errorMessages);
        }

    }

    public function importDataToDb($data, $accountId)
    {
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
                case 'Code':
                    $registrationCode = $value;
                    break;
            }
        }
        $acc = new Account();
        if ($acc->isAnyAccountLinkedTo($registrationCode)) {
            Rollbar::log(Level::ERROR, self::ERROR_LINK_REGISTRATION_CODE);
            return self::ERROR_LINK_REGISTRATION_CODE;
        }
        $arAccount = $acc->getById($accountId);
        $arAccount['UF_FLIGHT_NUM'] = $flightNum;
        $arAccount['UF_REG_CODE'] = $registrationCode;
        unset($arAccount['ID']);
        $entity = $acc->getEntityClass();
        $result = $entity::update($accountId, $arAccount);
        if (!$result->isSuccess()) {
            $errors = $result->getErrorMessages();
            Rollbar::log(Level::ERROR, self::CANT_SET_ACCOUNT_TO_BUSY . " $flightNum. Ошибка: " . $errors);
            return self::CANT_SET_ACCOUNT_TO_BUSY;
        }
        $errorFlag = false;
        foreach ($documents as $index => $document) {
            foreach ($document['lines'] as $line) {
                $findedLine = $entityClass::getList(array(
                    'select' => array('ID', 'UF_FLIGHT_NUM', 'UF_ACCOUNT_ID', 'UF_DOCUMENT_NUM', 'UF_AWB_NUM', 'UF_MATERIALNO', 'UF_MATERIALNAME'),
                    'filter' => array(
                        '=UF_ACCOUNT_ID' => 1, // @todo временно
                        '=UF_FLIGHT_NUM' => $flightNum,
                        '=UF_AWB_NUM' => $document['awbNum'],
                        '=UF_DOCUMENT_NUM' => $documentNumber,
                        '=UF_MATERIALNO' => $line['MaterialNo'],
                    )
                ))->fetch();
                if (!$findedLine) {
                    $res = $entityClass::add(array(
                        'UF_ACCOUNT_ID' => 1, // @todo временно
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
                        $errorFlag = true;
                        $errors = $res->getErrorMessages();
                        Rollbar::log(Level::ERROR, "Товар [" . trim($line['MaterialNo']) . "]: " . trim($line['MaterialName']) . " не загружен в БД. Ошибка: " . $errors);
                    }
                }

            }
        }
        if (!$errorFlag) {
            Rollbar::log(Level::NOTICE, 'Импорт номеклатуры из файла завершен успешно');
        }
        return $errorFlag;
    }
}
