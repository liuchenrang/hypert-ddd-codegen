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
    protected ?string $name = 'ddd:command';
    use TraitGen;
 
    
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
   
    
}
