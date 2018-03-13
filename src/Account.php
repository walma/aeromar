<?php
/**
 * User: Timkin Dmitriy
 * Date: 06.11.17
 * Time: 4:10
 */

namespace Walma\Aeromar;

use \Bitrix\Highloadblock as HL;

class Account
{
    private $hlName = 'Accounts';
    private $login;
    private $accountName, $accountToken, $accountActive, $accountState, $accountAccount;
    private $accountDateCreate, $accountAuthCode, $accountLocation;

    protected $hl, $entity, $entityClass, $query;

    public function __construct($arAccount = false)
    {
        $this->hl           = HL\HighloadBlockTable::getList(array('filter' => array('NAME' => $this->hlName)))->fetch();
        $this->entity       = HL\HighloadBlockTable::compileEntity( $this->hl ); //генерация класса
        $this->entityClass  = $this->entity->getDataClass();
        $this->query = new \Bitrix\Main\Entity\Query($this->entity);
        if ($arAccount !== false)
            $this->add($arAccount);
    }

    public function isValid()
    {
        return $this->getLogin() && $this->getAccountAccount();
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /*
     * Add account in HL
     * @param array $product
     *
     * */
    public function add($account)
    {
        if (!is_array($account)) throw new \Exception('Create Account: parameter is not an array');
//        if ($this->isExist($account['login'], $account[''])) return false;

        foreach ($account as $key => $value) {
            switch ($key) {
                case 'login':
                    $this->setLogin($value);
                    break;
                case 'active':
                    $this->setAccountActive($value);
                    break;
                case 'token':
                    $this->setAccountToken($value);
                    break;
                case 'name':
                    $this->setAccountName($value);
                    break;
                case 'state':
                    $this->setAccountState($value);
                    break;
                case 'date_create':
                    $this->setAccountDateCreate($value);
                    break;
                case 'auth_code':
                    $this->setAccountAuthCode($value);
                    break;
            }
        }
        if ($this->isValid()) {
            $accountEntity = $this->entityClass;
            $res = $accountEntity::add(array(
                'UF_LOGIN'          => $this->getLogin(),
                'UF_NAME'           => $this->getAccountName(),
                'UF_ACTIVE'         => $this->getAccountActive(),
                'UF_TOKEN'          => $this->getAccountToken(),
                'UF_STATE'          => $this->getAccountState(),
                'UF_AUTH_CODE'      => $this->getAccountAuthCode(),
                'UF_DATE_CREATE'    => $this->getAccountDateCreate(),
            ));
            if (!$res->isSuccess()) {
                $errors = $res->getErrorMessages();
                throw new \Exception("add Account: ". $errors);
            } else {
                return $this;
            }
        } else {
            throw new \Exception('add: Account is not Valid. Account obj: ' . print_r($this, true));
        }
    }

    /*
     * update account in HL
     * @param array $account
     *
     * */
    public function update($id, $account)
    {
        if (!is_array($account)) throw new \Exception('Update Account: parameter is not an array');
        if (!$this->getById($id)) return false;

        foreach ($account as $key => $value) {
            switch ($key) {
                case 'login':
                    $this->setLogin($value);
                    break;
                case 'active':
                    $this->setAccountActive($value);
                    break;
                case 'token':
                    $this->setAccountToken($value);
                    break;
                case 'name':
                    $this->setAccountName($value);
                    break;
                case 'state':
                    $this->setAccountState($value);
                    break;
                case 'date_create':
                    $this->setAccountDateCreate($value);
                    break;
                case 'auth_code':
                    $this->setAccountAuthCode($value);
                    break;
            }
        }
        $accountEntity = $this->entityClass;
        $res = $accountEntity::update($id, array(
            'UF_LOGIN'          => $this->getLogin(),
            'UF_NAME'           => $this->getAccountName(),
            'UF_ACTIVE'         => $this->getAccountActive(),
            'UF_TOKEN'          => $this->getAccountToken(),
            'UF_STATE'          => $this->getAccountState(),
            'UF_AUTH_CODE'      => $this->getAccountAuthCode(),
            'UF_DATE_CREATE'    => $this->getAccountDateCreate(),
        ));
        if (!$res->isSuccess()) {
            $errors = $res->getErrorMessages();
            throw new \Exception("update Account: ". $errors);
        } else {
            return $this;
        }
    }

    public function toArray()
    {
        /*if ($this->isValid()) */return array(
            'UF_LOGIN'          => $this->getLogin(),
            'UF_NAME'           => $this->getAccountName(),
            'UF_ACTIVE'         => $this->getAccountActive(),
            'UF_TOKEN'          => $this->getAccountToken(),
            'UF_STATE'          => $this->getAccountState(),
            'UF_AUTH_CODE'      => $this->getAccountAuthCode(),
            'UF_DATE_CREATE'    => $this->getAccountDateCreate(),
            );
//        else
//            throw new \Exception('toArray: Account is not Valid. ' . print_r($this, true));

    }

    public function getById($id)
    {
        $Query = $this->query;
        $Query->setSelect(array('*'));
        $filter = array('ID' => $id, 'UF_ACTIVE' => true);
        $Query->setFilter($filter);
        $result = $Query->exec();
        if ($row = $result->fetch()) {
            return $row;
        }
        return false;
    }

    public function getAllAccounts()
    {
        $Query = $this->query;
        $Query->setSelect(array('*'));
        $Query->setFilter(array('UF_ACTIVE' => true));
        $result = $Query->exec();
        if ($rowAll = $result->fetchAll()) {
            return $rowAll;
        }
        return false;

    }

    protected function isExist($token, $state)
    {
        $Query = $this->query;
        $Query->setSelect(array('*'));
        $Query->setFilter(array('UF_TOKEN' => $token, 'UF_STATE' => $state));
        $result = $Query->exec();
        $result = new CDBResult($result);

        if ($row = $result->fetch()) {
            return true;
        } else
            return false;
    }

    /**
     * @return mixed
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param mixed $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @return mixed
     */
    public function getAccountActive()
    {
        return $this->accountActive;
    }

    /**
     * @param mixed $accountActive
     */
    public function setAccountActive($accountActive)
    {
        $this->accountActive = $accountActive;
    }

    /**
     * @return mixed
     */
    public function getAccountState()
    {
        return $this->accountState;
    }

    /**
     * @param mixed $accountState
     */
    public function setAccountState($accountState)
    {
        $this->accountState = md5(654321);
    }

    /**
     * @return mixed
     */
    public function getAccountAuthCode()
    {
        return $this->accountAuthCode;
    }

    /**
     * @param mixed $accountAuthCode
     */
    public function setAccountAuthCode($accountAuthCode)
    {
        $this->accountAuthCode = $accountAuthCode;
    }

    /**
     * @return mixed
     */
    public function getAccountDateCreate()
    {
        return $this->accountDateCreate;
    }

    /**
     * @param mixed $accountDateCreate
     */
    public function setAccountDateCreate($accountDateCreate)
    {
        $this->accountDateCreate = $accountDateCreate;
    }

    /**
     * @return mixed
     */
    public function getAccountToken()
    {
        return $this->accountToken;
    }

    /**
     * @param mixed $accountToken
     */
    public function setAccountToken($accountToken)
    {
        $this->accountToken = $accountToken;
    }

    /**
     * @return mixed
     */
    public function getAccountName()
    {
        return $this->accountName;
    }

    /**
     * @param mixed $accountName
     */
    public function setAccountName($accountName)
    {
        $this->accountName = $accountName;
    }

    public function delete($id)
    {
        $Query = $this->query;
        $Query->setSelect(array('*'));
        $Query->setFilter(array('ID' => $id));
        $result = $Query->exec();
        $result = new CDBResult($result);

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