<?php declare(strict_types=1);

namespace TinyFramework\Opcache\Console\Commands;

use TinyFramework\Console\CommandAwesome;
use TinyFramework\Console\Input\InputDefinitionInterface;
use TinyFramework\Console\Input\InputInterface;
use TinyFramework\Console\Input\Option;
use TinyFramework\Console\Output\Components\Table;
use TinyFramework\Console\Output\OutputInterface;
use TinyFramework\Http\URL;

class TinyframeworkOpcacheStatusCommand extends CommandAwesome
{

    protected function configure(): InputDefinitionInterface
    {
        return parent::configure()
            ->option(Option::create(
                'url',
                null,
                Option::VALUE_IS_ARRAY,
                'Defined each deep node url, if needed.',
                []
            ))
            ->description('Print php-fpm opcache status.');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        parent::run($input, $output);
        $curls = [];
        $multi = curl_multi_init();
        $urls = $this->input->option('url')->value();
        $urls = empty($urls) ? [config('app.url')] : $urls;
        foreach ($urls as $host) {
            $url = (new URL($host))->path('/__opcache/status')->query([]);
            $curl = curl_init($url->__toString());
            curl_setopt($curl, CURLOPT_VERBOSE, $this->output->verbosity() > 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, ['key' => hash('sha512', config('app.secret'))]);
            curl_multi_add_handle($multi, $curl);
            $curls[] = [
                'host' => $host,
                'curl' => $curl,
            ];
        }

        $this->output->write('[....] Collection opcache information');
        $running = null;
        do {
            usleep(100);
            curl_multi_exec($multi, $running);
            curl_multi_select($multi);
        } while ($running);

        $table = new Table($this->output);
        $table->header(['node', 'enable', 'memory', 'hits']);
        foreach ($curls as $curl) {
            $status = curl_getinfo($curl['curl'], CURLINFO_HTTP_CODE);
            $row = [
                'host' => $curl['host'],
                'enable' => '?',
                'memory' => '?',
                'hits' => '?'
            ];
            if ($status === 200) {
                $json = json_decode(curl_multi_getcontent($curl['curl']), true);
                if ($this->output->verbosity() > 0) {
                    $this->output->box($curl['host']);
                    print_r($json['data']);
                }
                $row['enable'] = $json['data']['opcache_enabled'] ? 'yes' : 'no';
                $row['memory'] = round(
                        $json['data']['memory_usage']['used_memory'] /
                        ($json['data']['memory_usage']['used_memory'] + $json['data']['memory_usage']['used_memory']),
                        2
                    ) . '%';
                $row['hits'] = round($json['data']['opcache_statistics']['opcache_hit_rate'], 2) . '%';
            }
            $table->row($row);
            curl_multi_remove_handle($multi, $curl['curl']);
        }
        $this->output->write("\r[<green>DONE</green>]\n");
        $table->render();

        curl_multi_close($multi);
        return 0;
    }

}
