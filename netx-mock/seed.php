<?php

// #ddev-generated
/**
 * @file
 * Creates the Drupal side of a NetX mock setup. Run by `ddev netx-mock-seed`.
 *
 * Safe to run again: anything that already exists is left alone.
 */

use Drupal\key\Entity\Key;
use Drupal\media\Entity\MediaType;
use Drupal\netx_dam\Entity\NetxSource;

$source_id = getenv('NETX_MOCK_SOURCE') ?: 'mock';
$module_handler = \Drupal::moduleHandler();

foreach (['key', 'netx_dam'] as $required) {
  if (!$module_handler->moduleExists($required)) {
    print "The $required module is not enabled. Enable it first: ddev drush en netx_dam\n";
    return;
  }
}

$key_exists = fn (string $id) => (bool) Key::load($id);
$make_key = function (string $id, string $label, string $value) use ($key_exists): void {
  if ($key_exists($id)) {
    print "= key $id exists\n";
    return;
  }
  Key::create([
    'id' => $id,
    'label' => $label,
    'key_type' => 'authentication',
    'key_provider' => 'config',
    'key_provider_settings' => ['key_value' => $value],
    'key_input' => 'text_field',
  ])->save();
  print "+ key $id\n";
};

// The API token the mock expects (it only enforces it with STRICT_AUTH).
$make_key('netx_mock_token', 'NetX mock API token', 'dev-token-1234');

// The source. The module reaches the mock over ddev's Docker network, while
// browsers use the router URL.
if (NetxSource::load($source_id)) {
  print "= source $source_id exists\n";
}
else {
  NetxSource::create([
    'id' => $source_id,
    'label' => 'NetX mock',
    'api_url_base' => 'http://netx-mock:3000/api/rpc',
    'public_base_url' => 'https://' . (getenv('DDEV_HOSTNAME') ?: 'localhost') . ':3031',
    'netx_api_token' => 'netx_mock_token',
    'description' => 'Local mock from the ddev-netx-mock add-on. Not a real NetX.',
  ])->save();
  print "+ source $source_id\n";
}

// A media type, when the media module and netx_dam_media are available.
if ($module_handler->moduleExists('media') && $module_handler->moduleExists('netx_dam_media')) {
  if (MediaType::load('netx_asset')) {
    print "= media type netx_asset exists\n";
  }
  else {
    $type = MediaType::create([
      'id' => 'netx_asset',
      'label' => 'NetX asset',
      'source' => 'netx_dam_media',
      'source_configuration' => [
        'netx_dam_source' => $source_id,
        'file_extensions' => '',
        'thumbnails_directory' => 'public://netx_thumbnails/[date:custom:Y-m]',
      ],
    ]);
    $type->save();
    $field = $type->getSource()->createSourceField($type);
    $field->getFieldStorageDefinition()->save();
    $field->save();
    $type->set('source_configuration', ['source_field' => $field->getName()] + $type->get('source_configuration'));
    $type->save();
    print "+ media type netx_asset\n";
  }
}
else {
  print "- skipped the media type (enable media and netx_dam_media for it)\n";
}

// Webhook secret, when the receiver module is enabled. The mock's seed
// subscription sends this value as the body token.
if ($module_handler->moduleExists('netx_dam_webhooks')) {
  $make_key('netx_mock_webhook', 'NetX mock webhook secret', 'dev-webhook-secret');
  $config = \Drupal::configFactory()->getEditable('netx_dam_webhooks.settings');
  $secrets = $config->get('secrets') ?? [];
  if (empty($secrets[$source_id])) {
    $secrets[$source_id] = 'netx_mock_webhook';
    $config->set('secrets', $secrets)->save();
    print "+ webhook secret for $source_id\n";
  }
  else {
    print "= webhook secret for $source_id exists\n";
  }
}
else {
  print "- skipped the webhook secret (enable netx_dam_webhooks for it)\n";
}

print "\nNetX mock: https://" . (getenv('DDEV_HOSTNAME') ?: 'localhost') . ":3031/dashboard\n";
print "Explorer:  /admin/content/netx-dam/$source_id/explore\n";
