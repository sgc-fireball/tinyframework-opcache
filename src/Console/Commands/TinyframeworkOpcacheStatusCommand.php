<?php declare(strict_types=1);

namespace TinyFramework\Opcache\Console\Commands;

use TinyFramework\Console\CommandAwesome;
use TinyFramework\Console\Input\InputDefinitionInterface;
use TinyFramework\Console\Input\InputInterface;
use TinyFramework\Console\Input\Option;
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
                [config('app.url')]
            ))
            ->description('Print php-fpm opcache status.');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        parent::run($input, $output);

        // @TODO

        $curls = [];
        $multi = curl_multi_init();
        foreach ($this->input->option('url')->value() as $url) {
            $url = (new URL($url))->path('/__opcache/clear')->query([]);
            $curl = curl_init($url->__toString());
            curl_setopt($curl, CURLOPT_VERBOSE, $this->output->verbosity() > 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, ['key' => hash('sha512', config('app.secret'))]);
            curl_multi_add_handle($multi, $curl);
            $curls[] = $curl;
        }

        $this->output->write('[<green>....</green>] Collection opcache information');
        $running = null;
        do {
            usleep(100);
            curl_multi_exec($multi, $running);
        } while ($running);
        $this->output->write("\r[<green>DONE</green>]\n");

        foreach ($curls as $curl) {
            // $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($multi, $curl);
        }
        curl_multi_close($multi);
        return 0;
    }

}
