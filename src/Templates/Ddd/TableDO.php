<?php echo "<?php\r\n" ?>

<?php echo "namespace $namespace;\r\n" ?>

use App\Infrastructure\Model\Model;


/**
<?php
foreach ($fieldsInfo as $field) {
    $type = "mixed";
    if (isset($field['Type'])){
     if (   strpos($field['Field'], 'int') > -1){
         $type = "int";
     } if (   strpos($field['Field'], 'char') > -1){
            $type = "string";
        }
        if (   strpos($field['Field'], 'datetime') > -1){
            $type = "string";
        }
    }
    echo "* @property $type {$field['Field']} {$field['Comment']}\r\n";
}
?>
*/

class <?php echo $daoName;?>DO extends Model
{
    protected ?string $table = '<?php echo $tableName;?>';

    protected string $primaryKey = '<?php echo $pkName??'';?>';


}

