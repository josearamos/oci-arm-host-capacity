<?php
declare(strict_types=1);


// useful when script is being executed by cron user
$pathPrefix = ''; // e.g. /usr/share/nginx/oci-arm-host-capacity/

require "{$pathPrefix}vendor/autoload.php";

use Dotenv\Dotenv;
use Hitrov\Exception\ApiCallException;
use Hitrov\FileCache;
use Hitrov\OciApi;
use Hitrov\OciConfig;
use Hitrov\TooManyRequestsWaiter;

$envFilename = empty($argv[1]) ? '.env' : $argv[1];
$dotenv = Dotenv::createUnsafeImmutable(__DIR__, $envFilename);
$dotenv->safeLoad();

/*
 * No need to modify any value in this file anymore!
 * Copy .env.example to .env and adjust there instead.
 *
 * README.md now has all the information.
 */
$config = new OciConfig(
    'eu-madrid-3', //getenv('OCI_REGION'),
    'ocid1.user.oc1..aaaaaaaa5oryeshg4il7ddwrjuqowqlezwtwiisf36pvpjmhqrwjss2vh2fa', //getenv('OCI_USER_ID'),
    'ocid1.tenancy.oc1..aaaaaaaawobdx5kea7xh74r7ryl2m6apsktkldaegyvhi2k6egjfizaxa5vq', //getenv('OCI_TENANCY_ID'),
    '5d:96:ca:a2:82:03:70:e1:73:5c:cb:fc:15:c9:5d:04', //getenv('OCI_KEY_FINGERPRINT'),
    '-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCqiFonj6b6Xebz
NzRWkYlFX0NLsc8FERI8GR+XN8gvp58OtD1j/WIS3odYXnTUEOSKK6tYexijhp/7
OmoChTUcfHJKlnV4TLEIpLESZkZsVENKFrvpCbFciTvtFfaA48W7C1kAbDc7UN9j
QJzHCYuiyY795iHFR0qYzTWsM8unvF/FWJl8xHPM96SsQCJd7NW3fN74UDtCjJ8w
V/YUMXWdchjEufFE7Bsg2Y9zEmEZdFGzRzKlDwOQBwFi4clkaQeP6CpvmLLs9ZXB
MwaatDgwk1rFlGE6C5YuLBdUGgHgjb9T6U7AC/GMo7752oGhOFnht2pXADDhHnwJ
pArWsDSHAgMBAAECggEACcj+J1P6WFZoCJ+rzKXYMxdWxLKktt02ZyVn5yTnwzhC
i7Ty6thgtsV3Da6J1JtNidIcqFyT2tpANsmwyIk9NW+8nSQKBIeeBulwUaZ1twSU
wd0RFjucpjsnqaB/fwDS2Ts69ae/+ZLX5pmQBWm6TfJz1oTMfkXrdV4dDM8CChNQ
jaM9n36LVf7vJ4HfK7AnkHDO52cpsP8FjZD6X6r8/KtEp0XozDHztP/7ctJY3XZW
yYilDkpbrWAfPEjvcZXMFPutWxvJVSYvGS62fbSKguAEK+1yjBDRa+axpd6BcnkM
l0wqbhkWPYipByIrHxyKcxcQv3kXUwRsuTZbCNhmGQKBgQDVeAlVDal9OuxqEDRf
ehihXPsY4MSuaZudCHCen7sExcr2SNqeVgcnvL3NTtH2znAgf0dlZtsLgnCgYh9W
3BZxK8ZCETAVvznlks69CS4MDrOUlL1PHe1Qwpg/1gkML+3Q8MmnAKd1OTvdkpRY
8BVK1kJdeh7tAS5OD+9wfUgIqQKBgQDMglrbRkhHgpszF1R0U3xNF7umJmGTKuyu
p5QFRvaVWr/+muBxQQLXwWTOtwmZ7Npx0OKXTA/1e/yE/MytYgNwC5T3mS7A2tl0
Yq/fv/MlvSdaXLUAGlLK771DQKa0nFab47Or0HHFf9UJRqb60tuKbHbu8NZRla8v
QnB53QChrwKBgB0MRojSYRvvZrhQGDBd1vguROTvwRPSvyAxQ9Hx/mr511KIO2eM
YVDg+Br1/NBO6ycg6sA7rNb2GwlYENeq/0rLICFhYV+0M9avkX5bv25YmctAKjqX
4fE5aapWH8kOxJfIDEizaBYlgaX3CkTH9r3GRiWeNpMdtAVfWb++7IxxAoGACP/j
h6q8G4l/0uu/566U1b+pnlIqERPefoEZXnIU/9WShV3Darh1q5DzIrWjUoa9xixv
DEFoomDmZ+PIDgk2JYQc9hhjmlEHKLv/CVWlGZANX0idHngKFKwgJAmATIpktU97
5J+zogFSGqplRrxotNq5ESLAC68OmoHN+U7kbDkCgYARG0XZhK2mj6VUwZWylh+s
w5w6sT9UL1AtRUl/8fsRt1Wr2tLfYT/BQyS3BBPVZJnGKtYb5NVEKm4ATui+HIjO
mewKIqv8X9EG5mGoHo9YhuPV1K1CBP6upoy1GPla9cRARZJqc0ZAfyEUwXTvJs0j
6wgcgf4UTAYnTWlEXXMmdA==
-----END PRIVATE KEY-----', //getenv('OCI_PRIVATE_KEY_FILENAME'),
    'pqDa:EU-MADRID-3-AD-1', //getenv('OCI_AVAILABILITY_DOMAIN') ?: null, // null or '' or 'jYtI:PHX-AD-1' or ['jYtI:PHX-AD-1','jYtI:PHX-AD-2']
    getenv('OCI_SUBNET_ID'),
    'ocid1.image.oc1.eu-madrid-3.aaaaaaaanr6cbakkkhxxlhfexedcw2rm4hyzpkrrm6iij65r4delyizrl4na', //getenv('OCI_IMAGE_ID'),
    (int) getenv('OCI_OCPUS'),
    (int) getenv('OCI_MEMORY_IN_GBS')
);

$bootVolumeSizeInGBs = (string) getenv('OCI_BOOT_VOLUME_SIZE_IN_GBS');
$bootVolumeId = (string) getenv('OCI_BOOT_VOLUME_ID');
if ($bootVolumeSizeInGBs) {
    $config->setBootVolumeSizeInGBs($bootVolumeSizeInGBs);
} elseif ($bootVolumeId) {
    $config->setBootVolumeId($bootVolumeId);
}

$api = new OciApi();
if (getenv('CACHE_AVAILABILITY_DOMAINS')) {
    $api->setCache(new FileCache($config));
}
if (getenv('TOO_MANY_REQUESTS_TIME_WAIT')) {
    $api->setWaiter(new TooManyRequestsWaiter((int) getenv('TOO_MANY_REQUESTS_TIME_WAIT')));
}
$notifier = (function (): \Hitrov\Interfaces\NotifierInterface {
    /*
     * if you have own https://core.telegram.org/bots
     * and set TELEGRAM_BOT_API_KEY and your TELEGRAM_USER_ID in .env
     *
     * then you can get notified when script will succeed.
     * otherwise - don't mind OR develop you own NotifierInterface
     * to e.g. send SMS or email.
     */
    return new \Hitrov\Notification\Telegram();
})();

$shape = getenv('OCI_SHAPE');

$maxRunningInstancesOfThatShape = 1;
if (getenv('OCI_MAX_INSTANCES') !== false) {
    $maxRunningInstancesOfThatShape = (int) getenv('OCI_MAX_INSTANCES');
}

$instances = $api->getInstances($config);

$existingInstances = $api->checkExistingInstances($config, $instances, $shape, $maxRunningInstancesOfThatShape);
if ($existingInstances) {
    echo "$existingInstances\n";
    return;
}

if (!empty($config->availabilityDomains)) {
    if (is_array($config->availabilityDomains)) {
        $availabilityDomains = $config->availabilityDomains;
    } else {
        $availabilityDomains = [ $config->availabilityDomains ];
    }
} else {
    $availabilityDomains = $api->getAvailabilityDomains($config);
}

foreach ($availabilityDomains as $availabilityDomainEntity) {
    $availabilityDomain = is_array($availabilityDomainEntity) ? $availabilityDomainEntity['name'] : $availabilityDomainEntity;
    try {
        $instanceDetails = $api->createInstance($config, $shape, getenv('OCI_SSH_PUBLIC_KEY'), $availabilityDomain);
    } catch(ApiCallException $e) {
        $message = $e->getMessage();
        echo "$message\n";
//            if ($notifier->isSupported()) {
//                $notifier->notify($message);
//            }

        if (
            $e->getCode() === 500 &&
            strpos($message, 'InternalError') !== false &&
            strpos($message, 'Out of host capacity') !== false
        ) {
            // trying next availability domain
            sleep(16);
            continue;
        }

        // current config is broken
        return;
    }

    // success
    $message = json_encode($instanceDetails, JSON_PRETTY_PRINT);
    echo "$message\n";
    if ($notifier->isSupported()) {
        $notifier->notify($message);
    }

    return;
}
