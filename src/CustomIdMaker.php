<?php

namespace Krsrk\CustomId;


use Illuminate\Support\Facades\DB;


class CustomIdMaker
{
    const configArray = [
        'dbTable'             => '',
        'dbTableField'        => '',
        'queryCondition' => '',
        'lengthId'            => 0,
        'withPrefix'          => false,
        'prefixId'            => '',
        'prefixSeparator'     => '',
        'resetPrefix'         => false,
        'padId'               => false,
    ];

    protected $config;
    protected $fieldType = null;
    protected $fieldLength = null;

    public function __construct(array $config = [])
    {
        if (! empty($config)) {
            $this->_setConfig($config);
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function makeId() : string
    {
        $queryResult = DB::select($this->processQueryString());
        $queryId = $queryResult[0]->maxItem;

        return $this->processId($queryId);
    }

    /**
     * @param string $separator
     * @param bool $guidToUpperCase
     * @return string
     */
    public static function makeGuid(string $separator = '-', bool $guidToUpperCase = true) : string
    {
        mt_srand((double) microtime() * 10000);
        $randomChar = ($guidToUpperCase) ? strtoupper(md5(uniqid(rand(), true))) : md5(uniqid(rand(), true));

        $guid = substr($randomChar, 0, 8) . $separator
            . substr($randomChar, 8, 4) . $separator
            . substr($randomChar, 12, 4) . $separator
            . substr($randomChar, 16, 4) . $separator
            . substr($randomChar, 20, 12);

        return $guid;
    }

    /**
     * @param string $partSeparator
     * @param int $numberOfParts
     * @param int $lengthPart
     * @param bool $charToUpperCase
     * @return string
     */
    public static function makeSku(string $partSeparator = '-', int $numberOfParts = 4,  int $lengthPart = 4, bool $charToUpperCase = true) : string
    {
        mt_srand((double) microtime() * 10000);
        $randomChar = ($charToUpperCase) ? strtoupper(md5(uniqid(rand(), true))) : md5(uniqid(rand(), true));
        $skuStr = '';
        $offsetChar = 0;
        $separator = $partSeparator;

        for ($index=0; $index<$numberOfParts; $index++) {
            if ($index == ($numberOfParts-1)) {
                $separator = '';
            }

            $skuStr .= substr($randomChar, $offsetChar, $lengthPart) . $separator;
            $offsetChar += $lengthPart;
        }

        return $skuStr;
    }

    /**
     * @param int $id Resultset of the query count.
     * @return string
     */
    private function processId(int $id) : string
    {
        $maxIdIncrement = $id + 1;

        if ($this->config->padId) {
            $maxIdIncrement = str_pad($maxIdIncrement, $this->config->lengthId, '0', STR_PAD_LEFT);
        }

        if ($this->config->withPrefix) {
            $maxIdIncrement = $this->config->prefixId . $this->config->prefixSeparator . $maxIdIncrement;
        }

        return $maxIdIncrement;
    }

    /**
     * @return string
     * @throws \Exception If Query Condition is not valid.
     */
    private function processQueryString() : string
    {
        $baseQueryString = sprintf("SELECT COUNT(*) as maxItem FROM %s", $this->config->dbTable);
        $baseQueryStringCondition = '';

        if ($this->config->resetPrefix) {
            $baseQueryString = sprintf("SELECT COUNT(%s) maxItem from %s WHERE %s like %s",
                $this->config->dbTableField,
                $this->config->dbTable,
                $this->config->dbTableField,
                "'" . $this->config->prefixId . "%'"
            );
        }

        if ($this->config->queryCondition !== '') {
            $queryCondition = trim($this->config->queryCondition);
            $queryCondNeedle = ($this->config->resetPrefix) ? 'AND' : 'WHERE';
            $queryCondNeedleLength = strlen($queryCondNeedle);

            $isValidQueryStrCondtion = substr_compare($queryCondition, $queryCondNeedle, 0, $queryCondNeedleLength, true);

            if ($isValidQueryStrCondtion > 0) {
                throw new \Exception('Condición del query no valida.');
            }

            $baseQueryStringCondition = " $queryCondition";
        }

        return $baseQueryString . $baseQueryStringCondition;
    }

    /**
     * @param array $config
     * @throws \Exception
     */
    private function _setConfig(array $config): void
    {
        $configDiff = array_diff_key($config, self::configArray);
        if (! empty($configDiff)) {
            throw new \Exception('Faltan parametros de configuración');
        }

        foreach ($config as $key => $val) {
            if ($key == 'dbTable') {

                if (empty($val)) {
                    throw new \Exception('Nombre de la tabla no puede ir vacio');
                }

                if (! is_string($val)) {
                    throw new \Exception('Valor incorrecto para Nombre Tabla');
                }

            }

            if ($key == 'dbTableField') {

                if ($val == '') {
                    $config[$key] = 'id';
                }

                if (! is_string($val)) {
                    throw new \Exception('Valor incorrecto para el campo');
                }

                if (! $this->validateTableFieldExists($config['dbTable'], $config[$key])) {
                    throw new \Exception('No existe el campo en la tabla.');
                }

            }

            if ($key == 'lengthId') {

                if ($config[$key] > $this->fieldLength) {
                    throw new Exception('El length del ID no puede ser mayor al length del campo en la tabla');
                }

            }

            if ($config['withPrefix']) {

                if ($key == 'prefixId') {

                    if (empty($val)) {
                        throw new \Exception('Prefijo no puede ir vacio.');
                    }

                    if (! is_string($val)) {
                        throw new \Exception('Valor incorrecto para el prefijo');
                    }

                    if (in_array($this->fieldType, ['int', 'bigint', 'numeric'])) {
                        throw new \Exception('Solamente se puede poner prefijo en un campo tipo string de la tabla.');
                    }

                }

                if ($key == 'prefixSeparator') {

                    if (! empty($val)) {

                        if (! is_string($val)) {
                            throw new \Exception('Valor incorrecto para el separador');
                        }

                        if (! in_array($val, ['-', '_', '/', '.'])) {
                            throw new \Exception('Caracter no valido para el separador');
                        }

                        if (in_array($this->fieldType, ['int', 'bigint', 'numeric'])) {
                            throw new \Exception('Solamente se puede poner prefijo en un campo tipo string de la tabla.');
                        }

                    }

                }

                if ($key == 'resetPrefix') {

                    if (! is_bool($val)) {
                        throw new \Exception('Valor incorrecto para el reset Prefix');
                    }

                }

            }

            if ($key == 'padId') {

                if (! is_bool($val)) {
                    throw new \Exception('Valor incorrecto para el padId');
                }

            }
        }

        $this->config = (object)$config;
    }

    /**
     * @param string $tableName
     * @param string $tableField
     * @return bool
     */
    private function validateTableFieldExists(string $tableName, string $tableField) : bool
    {
        $queryData = DB::select("DESCRIBE $tableName");
        $tableExists = false;

        foreach ($queryData as $data) {
            if ($data->Field == $tableField) {
                $tableExists = true;
                $this->fieldType = $data->Type;
                preg_match("/(?<=\().+?(?=\))/", $data->Type, $fieldLength);
                $this->fieldLength = $fieldLength;
                break;
            }
        }

        return $tableExists;
    }
}
