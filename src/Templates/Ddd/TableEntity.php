<?php echo "<?php\r\n" ?>

<?php echo "namespace $namespace\r\n" ?>




class <?php echo $daoName;?>
{
<?php
foreach ($fieldsInfo as $field) {
    
    $lineInfo = '$'.$field['Field'] ;
    
    $lineInfo .= ";\r\n";
    echo "/**\r\n";
    echo "*" . $field['Comment'] . "\r\n";
    echo "*/\r\n";
    echo " public " . $lineInfo;
}
?>

}

