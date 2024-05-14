<?php

declare(strict_types=1);

namespace Yw\CodeGen\Command;

use App\Infrastructure\Company\AddressDO;
use App\Infrastructure\Company\CompanyDO;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Hyperf\DB\DB;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class CurdCommand extends HyperfCommand
{
    protected ?string $name = 'curd:command';
    use TraitGen;
    
    
    public function configure()
    {
        parent::configure();
        $this->addOption('action', 'a', InputOption::VALUE_REQUIRED, '业务层, domain/app', 'domain');
        $this->addOption('client', '', InputOption::VALUE_OPTIONAL, 'Api Client 命名', '');
        $this->addOption('service', '', InputOption::VALUE_OPTIONAL, 'Service名称', '');
        $this->addOption('execute', '', InputOption::VALUE_OPTIONAL, 'Execute名称,支持多个例如:c,u,q,d', '');
        $this->addOption('dto', '', InputOption::VALUE_OPTIONAL, 'Dto名称,支持多个例如:Company,Address', '');
        $this->addOption('cmd', '', InputOption::VALUE_OPTIONAL, 'cmd命名,支持多个例如:c,u,q,d', 'q');
        
        
        $this->addOption('domain', 'm', InputOption::VALUE_OPTIONAL, '业务领域', '');
        $this->addOption('table', 't', InputOption::VALUE_OPTIONAL, '数据表', '');
        $this->addOption('tablePrefix', 'p', InputOption::VALUE_OPTIONAL, '数据表前缀', '');
        $this->addOption('gateway', 'w', InputOption::VALUE_OPTIONAL, '生成gateway', '');
        $this->addOption('do', 'd', InputOption::VALUE_OPTIONAL, '生成do及entity', '');
        
    }
    
   
    
    public function handle()
    {
        $action = $this->input->getOption('action') ?? '';
        if (!in_array($action,['domain','app'])){
            $this->echoLine("当前动作不支持仅支持 domain,app ! 你的输入: $action");
            return;
        }
        if ($action == "domain"){
            $domain = $this->input->getOption('domain') ?? '';
            $table = $this->input->getOption('table') ?? '';
            $tablePrefix = $this->input->getOption('tablePrefix') ?? 'wa_';
            if ($domain){
                if (!$table || !$tablePrefix){
                    $this->echoLine("表没那个或前缀不存在!");
                }
                $this->mainAction($domain, $table, $tablePrefix);
            }
        }
        
        if ($action == "app"){
            $serviceName = $this->input->getOption('service')??'';
            $clientName = $this->input->getOption('client')??'';
            $executeNameList = $this->input->getOption('execute')??'';
            $cmdNameList = $this->input->getOption('cmd')??'';
            $dtoNameList = $this->input->getOption('dto')??'';
            if (!$clientName){
                $this->echoLine("请设置clientName");
                return;
            }
            $this->appAction($serviceName,$clientName,$executeNameList,$cmdNameList,$dtoNameList);
        }
    }
    public function appAction($serviceName,$clientName,$executeNameList,$cmdNameList,$dtoNameList){
        if ($serviceName){
            $content = $this->render("ServiceImpl.php", [
                "namespace" => "App\Module\\$clientName",
                "useList" => ["Yw\\$clientName\Client\Api\\" .$serviceName. "ServiceI"],
                "serviceName" => "$serviceName"
            ]);
            
            $this->write(BASE_PATH . "/app/Module/$clientName/".$serviceName."ServiceImpl.php", $content, false);
            
            $content = $this->render("ServiceI.php", [
                "namespace" => "Yw\\$clientName\Client\Api",
                "serviceName" => "$serviceName"
            ]);
            
            $this->write(BASE_PATH . "/ywext/$clientName/Client/Api/".$serviceName."ServiceI.php", $content, false);
        }
        if ($executeNameList){
            $executeLs = explode(',', $executeNameList);
            foreach ($executeLs as $exe){
                $suffix = "";
                if ($exe == 'c'){
                    $suffix = "AddExe";
                }
                if ($exe == 'u'){
                    $suffix = "UpdateExe";
                }
                if ($exe == 'q'){
                    $suffix = "QryExe";
                }
                if ($exe == 'd'){
                    $suffix = "DelExe";
                }
                if (!$suffix){
                    $this->echoLine("exe 参数不合法 仅支持 c,u,q,d");return;
                }
                $className = $serviceName . $suffix;
                $content = $this->render("ClassTmp.php", [
                    "namespace" => "App\Module\\$clientName\Executor",
                    "className" => $className
                ]);
                $this->write(BASE_PATH . "/app/Module/$clientName/Executor/$className.php", $content, false);
            }
        }
        if ($dtoNameList){
            $dtoLs = explode(',', $dtoNameList);
            foreach ($dtoLs as $dto){
                $dtoName = $dto . "DTO";
                $content = $this->render("ClassTmp.php", [
                    "namespace" => "Yw\\$clientName\Client\Dto\Data",
                    'className' => $dtoName
                ]);
                $this->write(BASE_PATH . "/ywext/$clientName/Client/Dto/Data/$dtoName.php", $content, false);
            }
        }
        if ($cmdNameList){
            $cmdLs = explode(',', $cmdNameList);
            foreach ($cmdLs as $exe){
                $suffix = "";
                if ($exe == 'c'){
                    $suffix = "AddCmd";
                }
                if ($exe == 'u'){
                    $suffix = "UpdateCmd";
                }
                if ($exe == 'q'){
                    $suffix = "Qry";
                }
                if ($exe == 'd'){
                    $suffix = "DelCmd";
                }
                if (!$suffix){
                    $this->echoLine("cmd 参数不合法 仅支持 c,u,q,d");
                }
                $className = $serviceName . $suffix;
                $content = $this->render("ClassTmp.php", [
                    "namespace" => "Yw\\$clientName\Client\Dto",
                    'className' => $className
                ]);
              
                $this->write(BASE_PATH . "/ywext/$clientName/Client/Dto/$className.php", $content, false);
            }
        }
        
        
    }
    
    
}
