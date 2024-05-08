<?php echo "<?php\r\n" ?>

<?php echo "namespace $namespace;\r\n" ?>

<?php foreach ($useList as $use){  ?>
    <?php echo "use $use;\r\n" ?>
<?php }  ?>

/**
*
*/




class <?php echo $serviceName;?>ServiceImpl implements <?php echo $serviceName;?>ServiceI
{

}
