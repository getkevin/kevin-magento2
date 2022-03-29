<?php

namespace Kevin\Payment\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Test extends Command
{
    protected $api;

    protected $logger;

    public function __construct(
        \Kevin\Payment\Api\Kevin $api,
        \Kevin\Payment\Logger\Logger $logger
    ) {
        $this->api = $api;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('kevin:test');
        $this->setDescription('Kevin test');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->critical('Message goes here');
        exit('aaaa');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $transactionId = '45d6001363fba0a201ace37cc7a2651e3603a13a';
        $transaction = $objectManager->create('Kevin\Payment\Model\Adapter')->getTransaction($transactionId);

        $additional = $transaction->getAdditionalInformation();
        $attr = [
            'PSU-IP-Address' => $additional['ip_address'],
            'PSU-IP-Port' => $additional['ip_port'],
            'PSU-User-Agent' => $additional['user_agent'],
            'PSU-Device-ID' => $additional['device_id'],
        ];
        //echo $transaction->getOrder()->getPayment()->getId(); die;

        $results = $this->api->getPayment($transactionId, $attr);
        if (isset($results['bankId'])) {
            $bank = $this->api->getBank($results['bankId']);
            if (isset($bank['id'])) {
                $payment = $transaction->getOrder()->getPayment();
                if (!$payment->getAdditionalInformation('bank_code') || !$payment->getAdditionalInformation('bank_name')) {
                    exit('aaa');
                }
                $payment->setAdditionalInformation('bank_name', $bank['officialName']);
                $payment->setAdditionalInformation('bank_code', $bank['id']);
                $payment->save();
            }
        }

        exit('aaa2');

        print_r($results);
        exit;

        $order = $objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId('000000104');
        echo $payment = $order->getPayment()->getMethodInstance()->getCode();
        exit;
        //$this->api->getBanks();
    }
}
