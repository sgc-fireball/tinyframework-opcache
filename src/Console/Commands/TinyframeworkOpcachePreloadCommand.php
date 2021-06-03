<?php declare(strict_types=1);

namespace TinyFramework\Opcache\Console\Commands;

use TinyFramework\Console\CommandAwesome;
use TinyFramework\Console\Input\InputDefinitionInterface;
use TinyFramework\Console\Input\InputInterface;
use TinyFramework\Console\Input\Option;
use TinyFramework\Console\Output\OutputInterface;
use TinyFramework\Http\URL;

class TinyframeworkOpcachePreloadCommand extends CommandAwesome
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
            ->description('Start the php-fpm opcache preload process.');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        parent::run($input, $output);

        $curls = [];
        $multi = curl_multi_init();
        $urls = $this->input->option('url')->value();
        $urls = empty($urls) ? [config('app.url')] : $urls;
        foreach ($urls as $host) {
            $url = (new URL($host))->path('/__opcache/preload')->query([]);
            $curl = curl_init($url->__toString());
            curl_setopt($curl, CURLOPT_VERBOSE, $this->output->verbosity() > 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, ['key' => hash('sha512', config('app.secret'))]);
            curl_multi_add_handle($multi, $curl);
            $curls[] = [
                'host' => $host,
                'curl' => $curl,
            ];
        }

        $this->output->write('Preload opcache on nodes:');
        $running = null;
        do {
            usleep(100);
            curl_multi_exec($multi, $running);
        } while ($running);

        $this->output->write("\n");
        foreach ($curls as $curl) {
            $status = curl_getinfo($curl['curl'], CURLINFO_HTTP_CODE);
            if ($status === 200) {
                $this->output->write("\r  [<green>DONE</green>] " . $curl['host'] . "\n");
            } else {
                $this->output->write("\r  [<red>FAIL</red>] " . $curl['host'] . "\n");
            }
            curl_multi_remove_handle($multi, $curl['curl']);
        }
        curl_multi_close($multi);
        return 0;
    }

}
