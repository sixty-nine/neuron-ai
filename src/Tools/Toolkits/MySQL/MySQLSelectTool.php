<?php

namespace NeuronAI\Tools\Toolkits\MySQL;

use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use PDO;

class MySQLSelectTool extends Tool
{
    public function __construct(protected PDO $pdo)
    {
        parent::__construct(
            'execute_select_query',
            'Use this tool only to run SELECT query against the MySQL database.
            This the tool to use only to gather information from the MySQL database.'
        );

        $this->addProperty(
            new ToolProperty(
                'query',
                'string',
                'The SELECT query you want to run against the database.',
                true
            )
        )->setCallable($this);
    }

    public function __invoke(string $query)
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
