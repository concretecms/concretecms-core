<?php
namespace Concrete\Core\Logging\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Database;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\User\User;

class DatabaseHandler extends AbstractProcessingHandler
{
    protected $initialized;
    private $statement;

    protected function write(array $record)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $params = [
            'channel' => $record['channel'],
            'level' => $record['level'],
            'message' => $record['formatted'],
            'time' => $record['datetime']->format('U'),
            'uID' => $record['extra']['user'][0] ?? 0,
            'cID' => $record['extra']['page'][0] ?? 0,
        ];
        try {
            $this->statement->execute($params);
        } catch (\Doctrine\DBAL\Exception\InvalidFieldNameException $x) {
            \Concrete\Core\Database\Schema\Schema::refreshCoreXMLSchema(['Logs']);
            $this->statement->execute($params);
        }
    }

    private function initialize()
    {
        $db = Database::get();

        $this->statement = $db->prepare(
            'INSERT INTO Logs (channel, level, message, time, uID, cID) VALUES (:channel, :level, :message, :time, :uID, :cID)'
        );

        $this->initialized = true;
    }


    /**
     * Clears all log entries. Requires the database handler.
     */
    public static function clearAll()
    {
        $db = Database::get();
        $db->Execute('delete from Logs');
    }

    /**
     * Clears log entries by channel. Requires the database handler.
     *
     * @param $channel string
     */
    public static function clearByChannel($channel)
    {
        $db = Database::get();
        $db->delete('Logs', array('channel' => $channel));
    }


}
