<?php declare(strict_types=1);

namespace DevStart;

class Database
{
    /**
     * The PDO object.
     *
     * @var \PDO
     */
    private $pdo;

    /**
     * Connected to the database.
     *
     * @var bool
     */
    private $isConnected;

    /**
     * PDO statement object.
     *
     * @var \PDOStatement
     */
    private $statement;

    /**
     * The database settings.
     *
     * @var array
     */
    protected $settings = [];

    /**
     * The parameters of the SQL query.
     * @var array
     */
    private $parameters = [];

    /**
     * Database constructor.
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;

        $this->connect();
    }

    private function connect()
    {
        $dsn = 'mysql:dbname=' . $this->settings['dbname'] . ';host=' . $this->settings['host'];

        try {
            $this->pdo = new \PDO($dsn, $this->settings['user'], $this->settings['password'], [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' .$this->settings['charset']
            ]);

            # Disable emulations and we can now log
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            $this->isConnected = true;

        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function closeConnection()
    {
        $this->pdo = null;
    }

    private function init(string $query, array $parameters = [])
    {
        if (!$this->isConnected) {
            $this->connect();
        }

        try {
            # Prepare query
            $this->statement = $this->pdo->prepare($query);

            # Bind parameters
            $this->bind($parameters);

            if (!empty($this->parameters)) {
                foreach ($this->parameters as $param => $value) {
                    if (is_int($value[1])) {
                        $type = \PDO::PARAM_INT;
                    } elseif (is_bool($value[1])) {
                        $type = \PDO::PARAM_BOOL;
                    } elseif (is_null($value[1])) {
                        $type = \PDO::PARAM_NULL;
                    } else {
                        $type = \PDO::PARAM_STR;
                    }

                    $this->statement->bindValue($value[0], $value[1], $type);
                }
            }

            $this->statement->execute();

        } catch (\PDOException $e) {
            exit($e->getMessage());
        }

        $this->parameters = [];
    }

    /**
     * @return void
     * @param array $parameters
     */
    private function bind(array $parameters): void
    {
        if (!empty($parameters) and is_array($parameters)) {
            $columns = array_keys($parameters);

            foreach ($columns as $i => &$column) {
                $this->parameters[sizeof($this->parameters)] = [
                    ':' . $column,
                    $parameters[$column]
                ];
            }
        }
    }

    /**
     * @param string $query
     * @param array $parameters
     * @param int $mode
     * @return array|int|null
     */
    public function query(string $query, array $parameters = [], $mode = \PDO::FETCH_ASSOC)
    {
        $query = trim(str_replace('\r', '', $query));

        $this->init($query, $parameters);

        $rawStatement = explode(' ', preg_replace("/\s+|\t+|\n+/", " ", $query));

        $statement = strtolower($rawStatement[0]);

        if ($statement === 'select' || $statement === 'show') {
            return $this->statement->fetchAll($mode);
        } elseif ($statement === 'insert' || $statement === 'update' || $statement === 'delete') {
            return $this->statement->rowCount();
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}