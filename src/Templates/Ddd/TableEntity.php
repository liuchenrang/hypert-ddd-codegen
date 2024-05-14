<?php echo "<?php\r\n" ?>

<?php echo "namespace $namespace\r\n" ?>




class <?php echo $daoName;?>Entity
{
<?php
foreach ($fieldsInfo as $field) {
    $type = "mixed";
    if (isset($field['Type'])){
        if (   strpos($field['Type'], 'int') > -1){
            $type = "int";
        } if (   strpos($field['Type'], 'char') > -1){
            $type = "string";
        }
        if (   strpos($field['Type'], 'datetime') > -1){
            $type = "string";
        }
    }
    $lineInfo = '$'.$field['Field'] ;
    
    $lineInfo .= ";\r\n";
    echo "/**\r\n";
    echo "*" . $field['Comment'] . "\r\n";
    echo "*/\r\n";
    echo " public $type " . $lineInfo;
}
?>

}

