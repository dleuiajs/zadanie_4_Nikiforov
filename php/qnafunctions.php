<?php
namespace otazkyodpovede;
require_once("db/dbfunctions.php");
use Exception, databaza\Db;

class QnA
{
    static function createData()
    {
        try {
            // pripojujeme sa k databáze
            $conn = Db::connect();

            // dopyt na vytvorenie databázy
            // $sqlCreateDb = "CREATE DATABASE IF NOT EXISTS sablona";
            // $statement = $conn->prepare($sqlCreateDb);
            // $statement->execute();

            // dopyt na vytvorenie tabuľky
            $sqlCreateTable = "
            CREATE TABLE IF NOT EXISTS qna (
                id INT AUTO_INCREMENT PRIMARY KEY, 
                otazka VARCHAR(255) NOT NULL,
                odpoved VARCHAR(1000) NOT NULL,
                UNIQUE(otazka, odpoved)
            );
        ";
            // vykonávame mysql dopyt
            $statement = $conn->prepare($sqlCreateTable);
            $statement->execute();
        } catch (Exception $e) {
            echo "Chyba: " . $e->getMessage();
        } finally {
            $conn = null;
        }
    }

    static function insertQnA()
    {
        try {
            // čítame dáta z json súboru
            $data = json_decode(file_get_contents("data/qna.json"), true);
            $otazky = $data["otazky"];
            $odpovede = $data["odpovede"];

            // pripojujeme sa k databáze    
            $conn = Db::connect();

            // spustenie transakcie
            $conn->beginTransaction();
            // dopyt na vloženie údajov do tabuľky (ak takéto údaje už existujú, nebudú vložené, pretože sú jedinečné a používa sa INSERT IGNORE)
            $sql = "INSERT IGNORE INTO qna (otazka, odpoved) VALUES (:otazka, :odpoved)";
            $statement = $conn->prepare($sql);

            // vkladáme každú otázku a odpoveď
            for ($i = 0; $i < count($otazky); $i++) {
                $statement->bindParam(":otazka", $otazky[$i]);
                $statement->bindParam(":odpoved", $odpovede[$i]);
                $statement->execute();
            }
            // dokončíme transakciu a urobíme zmeny trvalými
            $conn->commit();
        } catch (Exception $e) {
            echo "Chyba pri vkladaní dát do datábazý: " . $e->getMessage();
            // vraciame zmeny
            $conn->rollBack();
        } finally {
            // zatvárame pripojenie
            $conn = null;
        }
    }

    static function generateQnA()
    {
        echo '<section class="container">';
        try {
            // pripojujeme sa k databáze
            $conn = Db::connect();

            // dopyt na výber všetkých údajov z tabuľky
            $sql = "SELECT * FROM qna";
            // používame metódu query, pretože sa budú vracať údaje z tabuľky
            $statement = $conn->query($sql);

            // získavame údaje
            $result = $statement->fetchAll();

            //  robíme akordeón pre každý riadok v tabuľke
            foreach ($result as $row) {
                echo '<div class="accordion">
                    <div class="question">' . $row["otazka"] . '</div>
                    <div class="answer">' . $row["odpoved"] . '</div>
                  </div>';
            }
        } catch (Exception $e) {
            echo "Chyba pri čítaní dát z databázy: " . $e->getMessage();
        } finally {
            // zatvárame pripojenie
            $conn = null;
        }
        echo '</section>';
    }

}
?>