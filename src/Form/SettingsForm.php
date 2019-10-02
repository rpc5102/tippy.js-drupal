<?php

namespace Drupal\tippy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a form that configures Tippy.js settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal LibraryDiscovery service container.
   *
   * @var Drupal\Core\Asset\LibraryDiscovery
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  public function __construct(LibraryDiscovery $library_discovery) {
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tippy_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tippy.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get current settings.
    $tippy_config = $this->config('tippy.settings');

    // Load the Tippy.js libraries so we can use its definitions here.
    $tippy_library = $this->libraryDiscovery->getLibraryByName('tippy', 'tippy.js');

    $form['external'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('External file configuration'),
      '#description' => $this->t('These settings control the method by which the Tippy.js library is loaded. You can choose to use an external (full URL) or local (relative path) library by selecting a URL / path below, or you can use a local version of the file by leaving the box unchecked and downloading the library <a href=":remoteurl">:remoteurl</a> and installing locally at %installpath. See the README for more information.', [
        ':remoteurl' => $tippy_library['remote'],
        '%installpath' => '/libraries',
      ]),
      'use_cdn' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Use external file (CDN) / local file?'),
        '#description' => $this->t('Checking this box will cause the Tippy.js library to be loaded from the given source rather than from the local library file.'),
        '#default_value' => $tippy_config->get('use_cdn'),
      ],
      'external_location' => [
        '#type' => 'textfield',
        '#title' => $this->t('External File Location'),
        '#default_value' => $tippy_config->get('external_location'),
        '#size' => 80,
        '#description' => $this->t('Enter a source URL for the external Tippy.js library file you wish to use. Leave blank to use the default Tippy.js CDN.'),
        '#states' => [
          'disabled' => [
            ':input[name="use_cdn"]' => ['checked' => FALSE],
          ],
          'visible' => [
            ':input[name="use_cdn"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Validate URL.
    if (!empty($values['tippy_external_location']) && !UrlHelper::isValid($values['tippy_external_location'])) {
      $form_state->setErrorByName('tippy_external_location', $this->t('Invalid external library location.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Clear the library cache so we use the updated information.
    $this->libraryDiscovery->clearCachedDefinitions();

    // Set external file defaults.
    $default_location = 'https://unpkg.com/tippy.js@5';

    // Use default values if CDN is checked and the locations are blank.
    if ($values['use_cdn']) {
      if (empty($values['external_location'])) {
        // Choose the default depending on method.
        $values['external_location'] = $default_location;
      }
    }

    // Save the updated settings.
    $this->config('tippy.settings')
      ->set('use_cdn', $values['use_cdn'])
      ->set('external_location', (string) $values['external_location'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}