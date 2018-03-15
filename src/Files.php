<?php
/**
 * User: Timkin Dmitriy
 * Date: 06.11.17
 * Time: 4:10
 */

namespace Walma\Aeromar;

use \Bitrix\Highloadblock as HL;

class Files
{
    private $hlName = 'Files';
    private $fileName, $fileImportCompleted;
    private $fileDateCreate, $fileDateCompleted;

    protected $hl, $entity, $entityClass, $query;

    public function __construct($arFile = false)
    {
        $this->hl           = HL\HighloadBlockTable::getList(array('filter' => array('NAME' => $this->hlName)))->fetch();
        $this->entity       = HL\HighloadBlockTable::compileEntity( $this->hl ); //генерация класса
        $this->entityClass  = $this->entity->getDataClass();
        $this->query = new \Bitrix\Main\Entity\Query($this->entity);
        if ($arFile !== false) {
            $this->add($arFile);
        }
        return $this;
    }

    public function isValid()
    {
        return $this->getFileName();
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    /*
     * Add account in HL
     * @param array $product
     *
     * */
    public function add($file)
    {
        if (!is_array($file)) throw new \Exception('Create File: parameter is not an array');

        foreach ($file as $key => $value) {
            switch ($key) {
                case 'filename':
                    $this->setFileName($value);
                    break;
            }
        }
        $fileEntity = $this->entityClass;
        $res = $fileEntity::add(array(
            'UF_FILENAME'       => $this->getFileName(),
            'UF_COMPLETED'      => false,
            'UF_DATE_CREATE'    => $this->setFileDateCreate(),
        ));
        if (!$res->isSuccess()) {
            $errors = $res->getErrorMessages();
            throw new \ErrorException("add File: ". print_r($errors, true));
        } else {
            return $res->getId();
        }
    }

    /*
     * imprt completed in HL
     * @param int $id
     * */
    public function setFileCompleted($id)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        // Формат UTC даты:
        // YYYY-MM-DD hh:mm:ss
        $fileDateCreate = date('Y-m-d H:i:s', $dateTime->getTimestamp());
        $fileEntity = $this->entityClass;
        $res = $fileEntity::update($id, array(
            'UF_COMPLETED'     => true,
            'UF_DATE_COMPLETED' => $fileDateCreate,
        ));
        if (!$res->isSuccess()) {
            $errors = $res->getErrorMessages();
            throw new \ErrorException("Errors set file import complete: ". $errors);
        } else {
            return $this;
        }
    }

    public function toArray()
    {
        return array(
            'UF_FILENAME'      => $this->getFileName(),
            'UF_COMPLETED'     => $this->getFileImportCompleted(),
            'UF_DATE_COMPLETED' => $this->getFileDateCompleted(),
            'UF_DATE_CREATE'   => $this->getFileDateCreate(),
        );
    }

    public function getById($id)
    {
        $Query = $this->query;
        $Query->setSelect(array('*'));
        $filter = array('ID' => $id);
        $Query->setFilter($filter);
        $result = $Query->exec();
        if ($row = $result->fetch()) {
            return $row;
        }
        return false;
    }

    public function getAllFiles($onlyCompleted = false)
    {
        $Query = $this->query;
        $Query->setSelect(array('*'));
        if ($onlyCompleted) {
            $Query->setFilter(array('UF_COMPLETED' => true));
        }
        $result = $Query->exec();
        if ($rowAll = $result->fetchAll()) {
            return $rowAll;
        }
        return false;

    }

    public function isExist($filename)
    {
        $Query = $this->query;
        $Query->setSelect(array('*'));
        $Query->setFilter(array('UF_FILENAME' => $filename));
        $result = $Query->exec();
        if ($row = $result->fetch()) {
            return true;
        } else
            return false;
    }

    /**
     * @return mixed
     */
    public function getFileImportCompleted()
    {
        return $this->fileImportCompleted;
    }

    /**
     * @param mixed $fileImportCompleted
     */
    public function setFileImportCompleted($fileImportCompleted)
    {
        $this->fileImportCompleted = $fileImportCompleted;
    }

    /**
     * @return mixed
     */
    public function getFileDateCompleted()
    {
        return $this->fileDateCompleted;
    }

    /**
     * @param mixed $fileDateCompleted
     */
    public function setFileDateCompleted($fileDateCompleted = 'now')
    {
        $dateTime = new \DateTime($fileDateCompleted, new \DateTimeZone('UTC'));
        // Формат UTC даты:
        // YYYY-MM-DD hh:mm:ss
        $fileDateCompleted = date('Y-m-d H:i:s', $dateTime->getTimestamp());
        $this->fileDateCompleted = $fileDateCompleted;
    }

    /**
     * @return mixed
     */
    public function getFileDateCreate()
    {
        return $this->fileDateCreate;
    }

    /**
     * @param mixed $fileDateCreate
     */
    public function setFileDateCreate($fileDateCreate = 'now')
    {
        $dateTime = new \DateTime($fileDateCreate, new \DateTimeZone('UTC'));
        // Формат UTC даты:
        // YYYY-MM-DD hh:mm:ss
        $fileDateCreate = date('Y-m-d H:i:s', $dateTime->getTimestamp());
        $this->fileDateCreate = $fileDateCreate;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param mixed $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    public function delete($id)
    {
        $Query = $this->query;
        $Query->setSelect(array('*'));
        $Query->setFilter(array('ID' => $id));
        $result = $Query->exec();
        if ($row = $result->Fetch()) {
            $account = $this->entityClass;
            $res = $account::delete($row['ID']);
            if (!$res->isSuccess()) {
                $errors = $res->getErrorMessages();
                throw new \Exception("delete: ". $errors);
            } else {
                return true;
            }
        } else
            throw new \Exception("delete: Account with ID: $id not found");
    }

}