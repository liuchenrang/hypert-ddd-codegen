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

#[Command]
class DddCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('ddd:command');
    }
    
    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }
    
    protected function getArguments()
    {
        return [
            ['domain', InputArgument::REQUIRED, '业务'],
            ['table', InputArgument::REQUIRED, '表名'],
            ['tablePrefix', InputArgument::OPTIONAL, '表前缀'],
        ];
    }
    
    public function handle()
    {
        $domain = $this->input->getArgument('domain') ?? '';
        $table = $this->input->getArgument('table') ?? '';
        $tablePrefix = $this->input->getArgument('tablePrefix') ?? 'wa_';
        $this->mainAction($domain, $table, $tablePrefix);
        $this->line('info');
    }
    
    public function mainAction($domain, $table, $tablePrefix, $select = '')
    {
        $tableModule = ucfirst($this->toCamelCase($table));
        
        
        $tableName = $tablePrefix . $table;
        /**
         * @var $db Db
         */
        $db = ApplicationContext::getContainer()->get(DB::class);
        $fieldsInfo = $db->query("SHOW full COLUMNS FROM {$tablePrefix}{$table} ");
        
        array_walk($fieldsInfo, function (&$fieldInfo) {
            if ($fieldInfo['Default'] == 'CURRENT_TIMESTAMP') {
                $fieldInfo['Default'] = "";
            }
            $fieldInfo['isInt'] = strpos($fieldInfo['Type'], 'int') !== false;
        });
        $pkArr = array_filter($fieldsInfo, function ($item) {
            return $item['Key'] == 'PRI';
        });
        if ($pkArr == null) {
            throw new \RuntimeException("找不到主键！");
        }
        
        $pk = $pkArr[0];
        $namespaceModelEnum = $this->getInfrastructureNs($table, $domain);
        $content = $this->render("TableDo.php", [
            "ns" => $tableModule,
            'daoName' => $tableModule,
            'fieldsInfo' => $fieldsInfo,
            "namespace" => $namespaceModelEnum,
            'pkName' => $pk['Field'],
            'tableName' => $tableName
        ]);
        
        $this->write(BASE_PATH . "/app/infrastructure/$domain/{$tableModule}DO.php", $content, $select);
        
        
        $content = $this->render("TableEntity.php", [
            "ns" => $tableModule,
            'daoName' => $tableModule,
            'fieldsInfo' => $fieldsInfo,
            "namespace" => "App\Domain\\$domain;",
            'pkName' => $pk['Field'],
            'tableName' => $tableName
        ]);
        
        $this->write(BASE_PATH . "/app/Domain/$domain/{$tableModule}.php", $content, true);
        
        
        $content = $this->render("GatewayI.php", [
            'domain' => $domain,
            "namespace" => "App\Domain\\$domain\Gatewa",
        ]);
        $this->write(BASE_PATH . "/app/Domain/$domain/Gateway/{$domain}Gateway.php", $content, false);
        
        $content = $this->render("GatewayImpl.php", [
            'domain' => $domain,
            "namespace" => "App\Infrastructure\\$domain\Gateway",
        ]);
        $this->write(BASE_PATH . "/app/Infrastructure/$domain/Gateway/{$domain}GatewayImpl.php", $content, false);
        
    }
    
    protected function getInfrastructureNs($table, $domain)
    {
        return "App\Infrastructure\\" . $domain;
    }
    
    public function getIdeHepler()
    {
        $arrayService = [];
        foreach ($this->di->getServices() as $service) {
            try {
                $serviceObject = $service->resolve();
                if (is_object($serviceObject)) {
                    $className = get_class($serviceObject);
                    $reflectionClass = new ReflectionClass($className);
                    $interface = $reflectionClass->getInterfaceNames();
                    if ($interface) {
                        $useName = $interface[0];
                    } else {
                        $useName = $className;
                    }
                    $array = explode("/", $useName);
                    $returnName = array_pop($array);
                    $arrayService[$returnName] = [
                        "actionName" => "get" . ucfirst($service->getName()),
                        "useName" => $useName,
                        "returnName" => $returnName,
                    ];
                }
            } catch (Exception $e) {
                //                echo $e->getMessage();
            }
        }
        return $arrayService;
    }
    
    //驼峰命名转下划线命名
    public function toUnderScore($str): string
    {
        $dst = preg_replace_callback('/([A-Z]+)/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return trim(preg_replace('/_{2,}/', '_', $dst), '_');
    }
    
    //下划线命名到驼峰命名
    public function toCamelCase($str)
    {
        $array = explode('_', $str);
        $result = $array[0];
        $len = count($array);
        if ($len > 1) {
            for ($i = 1; $i < $len; $i++) {
                $result .= ucfirst($array[$i]);
            }
        }
        return $result;
    }
    
    public function render($template, $data)
    {
        $tempatePath = __DIR__ . "/../Templates/Ddd/";
        extract($data);
        ob_start();
        include $tempatePath . $template;
        $data = ob_get_contents();
        ob_clean();
        return $data;
    }
    
    public function echoLine($msg)
    {
        $this->line($msg);
    }
    
    public function write($dirFile, $content, $override = true)
    {
        if (file_exists($dirFile) && !$override) {
            $this->echoLine('文件存在 ' . $dirFile . " 不覆盖");
            return;
        }
        $dir = dirname($dirFile);
        @mkdir($dir, 0777, true);
        file_put_contents($dirFile, $content);
        
    }
    
    public function ctrlAction($argv)
    {
        $module = $argv[1];
        $basePath = $argv[2];
        $tableName = $argv[3];
        
        $content = $this->render("Module.php", ["serviceName" => $module, 'daoName' => $module]);
        foreach ($this->moduels as $k => $v) {
            @mkdir(APPLICATION_PATH . "{$basePath}/Modules/$module/" . $v, 0777, true);
        }
        $this->write(APPLICATION_PATH . "{$basePath}/Modules/$module/Module.php", $content);
    }
    
    
    public function renderHelp($template, $data)
    {
        $tempatePath = APPLICATION_PATH . "/framework/Generator/Templates/";
        extract($data);
        ob_start();
        include $tempatePath . $template;
        $data = ob_get_contents();
        ob_clean();
        return $data;
    }
    
    public function ideHelperAction($argv)
    {
        $ideHepler = $this->getIdeHepler();
        $content = $this->render("IdeHelper.php", ["services" => $ideHepler]);
        $this->write(APPLICATION_PATH . ".idehelper.php", $content);
    }
    
    public function parseParams($argv)
    {
        $params = [];
        $data = array_reduce($argv, function ($last, $v) {
            $explode = explode("=", $v);
            $last[$explode[0]] = $explode[1];
            return $last;
        }, $params);
        return $data;
    }
    
    public function getByKey($array, $key, $default)
    {
        return $array[$key] ? $array[$key] : $default;
    }
    
    public function arrayUniqCheck($ar)
    {
        $uniq = array();
        foreach ($ar as $value) {
            $uniq[strtolower($value)] = $value;
        }
        return array_values($uniq);
    }
    
    public function autoDIAction($argv)
    {
        $params = $this->parseParams($argv);
        $appWritePath = $this->getByKey($params, "appToPath", "app");
        $moduleWritePath = $this->getByKey($params, "moduleToPath", "app/Modules");
        $fileName = $this->getByKey($params, "fileName", "AutoDiProvider.php");
        $dryRun = $this->getByKey($params, "dryRun", false);
        $modulePath = $this->getByKey($params, "modulePath", "app/Modules");
        $models = $this->getByKey($params, "models", []);
        $glob = glob("app/Model/*.php");
        foreach ($glob as $file) {
            $models[] = basename($file, ".php");
        }
        $services = $models;
        $glob = glob("app/Service/*.php");
        foreach ($glob as $file) {
            $services[] = basename($file, "Service.php");
        }
        $appDiContent = $this->renderHelp("AutoDiProvider.php", ["namespace" => ucfirst("app"), "models" => $models, "services" => array_unique($services), 'className' => basename($fileName, ".php")]);
        
        $waitWrite = [["path" => $appWritePath, "fileName" => $fileName, "content" => $appDiContent]];
        
        $aModule = $this->getByKey($params, "module", "");
        if ($aModule) {
            $globModules[] = $aModule;
        } else {
            $globModules = scandir($modulePath);
        }
        foreach ($globModules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            $moduleFileName = $module . $fileName;
            $moduleModels = glob(join(DIRECTORY_SEPARATOR, [$modulePath, $module, "Model", "*.php"]));
            $models = [];
            $services = [];
            foreach ($moduleModels as $file) {
                $models[] = basename($file, ".php");
            }
            $moduleNameSpacePrefix = str_replace("/", "/", $modulePath);
            $namespace = join("/", [$moduleNameSpacePrefix, $module]);
            
            $services = $models;
            $glob = glob(join(DIRECTORY_SEPARATOR, [$modulePath, $module, "Service", "*Service.php"]));
            foreach ($glob as $file) {
                $services[] = basename($file, "Service.php");
            }
            
            
            $arrayUniqCheck = $this->arrayUniqCheck($services);
            $arrayUniq = array_unique($services);
            $diff = array_diff($arrayUniq, $arrayUniqCheck);
            if ($diff) {
                echo "请检查model,和service的大小写拼写，异常模块";
                var_dump($diff, $arrayUniq, $arrayUniqCheck);
                exit;
            }
            $appDiContent = $this->renderHelp("AutoDiProvider.php", [
                "namespace" => ucfirst($namespace), "models" => array_unique($models),
                'className' => basename($moduleFileName, ".php"),
                "services" => $arrayUniqCheck
            ]);
            $waitWrite[] = ["path" => join(DIRECTORY_SEPARATOR, [$moduleWritePath, $module]), "fileName" => $moduleFileName, "content" => $appDiContent];
        }
        
        if ($dryRun) {
            foreach ($waitWrite as $value) {
                $file = $value["path"] . DIRECTORY_SEPARATOR . $value['fileName'];
                echo "writeFile {$file} \r\n";
                echo "writeContent \r\n{$value["content"]}\r\n";
            }
        } else {
            $loader = [];
            foreach ($waitWrite as $value) {
                @mkdir($value['path'], 655, true);
                $file = $value["path"] . DIRECTORY_SEPARATOR . $value['fileName'];
                echo "writeFile  {$file} \r\n";
                file_put_contents($file, $value['content']);
            }
        }
        $updateIdeHelper = $this->getByKey($params, "updateIdeHelper", false);
        if ($updateIdeHelper) {
            $this->ideHelperAction($argv);
        }
    }
    
    public static function parseComment($field): ?array
    {
        $data = null;
        if (strpos($field['Comment'], "#") > -1 || strpos($field['Comment'], "@") > -1 || strpos($field['Comment'], "=") > -1) {
            $statusInfo = explode("#", $field['Comment']);
            $data = [];
            if (count($statusInfo) >= 2) {
                $info = [
                    "comment" => $statusInfo[0],
                    "name" => $field['Field'],
                    "items" => []
                ];
                array_shift($statusInfo);
                
                foreach ($statusInfo as $item) {
                    
                    $statusItemData = explode("@", $item);
                    if (count($statusItemData) == 2) {
                        $status = explode('=', $statusItemData[1]);
                        $info['items'][] = [
                            "comment" => $statusItemData[0],
                            "key" => $status[0],
                            "value" => $status[1],
                        ];
                    }
                    
                }
                $data = $info;
            }
        }
        return $data;
    }
    
}
