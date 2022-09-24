<?php declare(strict_types=1);

namespace Melv\AutoExchangeRates\Command;

use Exception;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchCurrencyFactors extends Command
{
    private SystemConfigService $systemConfigService;

    private EntityRepositoryInterface $currencyRepository;

    public function __construct
    (
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $currencyRepository
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->currencyRepository = $currencyRepository;
        parent::__construct();
    }

    protected static $defaultName = 'melv:fetch-currency-factor';

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $defaultCurrencyId = $this->systemConfigService->get('MelvAutoExchangeRates.config.defaultCurrencyId');
        $currencyFactorNeeded = $this->systemConfigService->get('MelvAutoExchangeRates.config.currencyFactorNeeded');
        $apiKey = $this->systemConfigService->get('MelvAutoExchangeRates.config.apiKey');

        if(!$defaultCurrencyId || !$currencyFactorNeeded || !$apiKey) {
            return Command::INVALID;
        }

        $currencies = $this->getCurrencies($currencyFactorNeeded);
        $defaultCurrency = $this->getDefaultCurrency($defaultCurrencyId);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.apilayer.com/exchangerates_data/latest?symbols={$currencies}&base={$defaultCurrency}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: text/plain",
                "apikey: {$apiKey}"
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET"
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $this->updateCurrencyFactor($response, $output);

        return Command::SUCCESS;
    }

    private function getCurrencies(array $currencyFactorNeeded)
    {
        $criteria = new Criteria($currencyFactorNeeded);
        $currencies = $this->currencyRepository->search($criteria, new Context(new SystemSource()));

        if($currencies->getTotal() == 0) {
            return Command::SUCCESS;
        }

        $currencyIsoCodes = [];
        foreach($currencies->getEntities() as $currency) {
            $currencyIsoCodes[] = $currency->getIsoCode();
        }

        return implode(',', $currencyIsoCodes);
    }

    private function getDefaultCurrency(string $defaultCurrencyId)
    {
        $criteria = new Criteria([$defaultCurrencyId]);
        $defaultCurrency = $this->currencyRepository->search($criteria, new Context(new SystemSource()))->first();

        return $defaultCurrency->getIsoCode();
    }

    private function updateCurrencyFactor($response, $output) {
        $ratesArray = json_decode($response, true);
        $factors = $ratesArray['rates'];
        $context = new Context(new SystemSource());
        $floatRounding = $this->systemConfigService->get('MelvAutoExchangeRates.config.floatRounding');

        foreach ($factors as $key => $value) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('isoCode', $key));
            $currency = $this->currencyRepository->search($criteria, $context)->first();

            if(isset($currency->getCustomFields()['melv_currency_safety_margin'])) {
                $output->writeln("<info>{$key}:</info> Original factor {$value}");
                $safetyMargin = $currency->getCustomFields()['melv_currency_safety_margin'];
                $output->writeln("<info>{$key}:</info> Adjusting factor with {$safetyMargin}%");
                $value *= (1 - $safetyMargin / 100);
                $output->writeln("<info>{$key}:</info> New calculated factor {$value}");
            }

            if(isset($floatRounding)) {
                $value = round($value, $floatRounding);
                $output->writeln("<info>{$key}:</info> Adjusted to {$value} with {$floatRounding} decimals");
            }

            if($currency) {
                $this->currencyRepository->update([
                    [
                        'id' => $currency->getId(),
                        'factor' => $value
                    ]
                ], $context);
                $output->writeln("<info>{$key}:</info> Updated with factor {$value}");
            }
        }
    }
}
