<?php

declare(strict_types=1);

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
            ->option(
                Option::create(
                    'url',
                    'u',
                    Option::VALUE_IS_ARRAY,
                    'Defined each deep node url, if needed.',
                    []
                )
            )
            ->option(
                Option::create(
                    'json',
                    'j',
                    Option::VALUE_NONE,
                    'Enable json output.',
                    []
                )
            )
            ->description('Print php-fpm opcache status.');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        parent::run($input, $output);
        $jsonOutput = (bool)$this->input->option('json')->value();

        $curls = [];
        $multi = curl_multi_init();
        $urls = $this->input->option('url')->value();
        $urls = is_array($urls) ? $urls : [];
        $urls = (bool)count($urls) ? $urls : config('opcache.urls');
        $urls = (bool)count($urls) ? $urls : [config('app.url')];
        $verbosity = 0 < (int)$this->output->verbosity();
        foreach ($urls as $host) {
            $url = (new URL($host))->path('/__opcache/status')->query([]);
            $curl = curl_init($url->__toString());
            curl_setopt($curl, CURLOPT_VERBOSE, $verbosity);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, ['key' => hash('sha512', config('app.secret'))]);
            curl_multi_add_handle($multi, $curl);
            $curls[] = [
                'host' => $host,
                'curl' => $curl,
            ];
        }

        if (!$jsonOutput) {
            $this->output->write('[....] Collection opcache information');
        }
        do {
            usleep(100);
            curl_multi_exec($multi, $running);
            curl_multi_select($multi);
        } while ($running);

        $table = new Table($this->output);
        $table->header(['host', 'node', 'enable', 'memory', 'hits', 'scripts']);
        foreach ($curls as $curl) {
            $status = curl_getinfo($curl['curl'], CURLINFO_HTTP_CODE);
            $row = [
                'host' => $curl['host'],
                'node' => '?',
                'enable' => '?',
                'memory' => '?',
                'hits' => '?',
                'scripts' => '?',
            ];
            if ($status === 200) {
                $body = curl_multi_getcontent($curl['curl']);
                $json = json_decode($body, true);
                if ($verbosity) {
                    $this->output->box($curl['host']);
                }
                if ($json['data'] === false) {
                    $row['node'] = '-';
                    $row['enable'] = 'no';
                    $row['memory'] = '0%';
                    $row['hits'] = '0%';
                    $row['scripts'] = '0';
                } else {
                    $row['node'] = $json['data']['node'];
                    $row['enable'] = (bool)$json['data']['opcache_enabled'] ? 'yes' : 'no';
                    $row['memory'] = round(
                            $json['data']['memory_usage']['used_memory'] /
                            ($json['data']['memory_usage']['used_memory'] + $json['data']['memory_usage']['used_memory']),
                            2
                        ) . '%';
                    $row['hits'] = round($json['data']['opcache_statistics']['opcache_hit_rate'], 2) . '%';
                    $row['scripts'] = (string)$json['data']['opcache_statistics']['num_cached_scripts'];
                }
            }
            $table->row($row);
            curl_multi_remove_handle($multi, $curl['curl']);
        }
        curl_multi_close($multi);

        if (!$jsonOutput) {
            $this->output->write("\r[<green>DONE</green>]\n");
            $table->render();
        } else {
            $this->output->write(
                json_encode(
                    array_map(
                        fn($row) => array_combine($table->header(), $row),
                        $table->rows()
                    )
                )
            );
        }

        return 0;
    }
}
