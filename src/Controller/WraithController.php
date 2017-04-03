<?php

namespace Drupal\wraith\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

/**
 */
class WraithController extends ControllerBase {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The config factory object.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new XmlSitemapController object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state, ConfigFactory $configFactory) {
    $this->state = $state;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory')
    );
  }

  /**
   * Provides the sitemap in XML format.
   *
   * @throws NotFoundHttpException
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The sitemap in XML format or plain text if xmlsitemap_developer_mode flag
   *   is set.
   */
  public function renderCaptureYaml() {

    $config = $this->configFactory->get('wraith.settings');
    $percentage = $config->get('percentage');
    $languages = $config->get('languages');

    $types = ['node', 'taxonomy_term', 'media'];
    $active_bundles = [];
    foreach ($types as $type) {
      $values = $config->get('type_' . $type);
      if (!empty($values)) {
        $active_bundles[$type] = $values;
      }
    }

    $urls = [];

    foreach ($active_bundles as $entity_type => $bundles) {
      foreach ($bundles as $bundle) {
        foreach ($languages as $language) {
          $urls += $this->getUrls($entity_type, $bundle, $language, $percentage);
        }
      }
    }
    $build = [
      '#theme' => 'wraith_capture',
      '#links' => $urls,
      '#current_domain' => $config->get('current_domain'),
      '#new_domain' => $config->get('new_domain')
    ];

    $output = render($build);
    $response = new Response($output);
    $response->headers->set('Content-type', 'texts; charset=utf-8');
    $response->headers->set('X-Robots-Tag', 'noindex, follow');
    return $response;
  }

  private function getUrls($entity_type, $bundle, $langcode, $percentage) {
    $keys = \Drupal::entityTypeManager()->getStorage($entity_type)->getEntityType()->getKeys();

    $count_query = \Drupal::entityQuery($entity_type);
    $count_query->condition($keys['bundle'], $bundle);
    $count_query->condition('langcode', $langcode);
    $count_result = $count_query->count()->execute();

    $url_to_fetch_count = round($count_result / 100 * $percentage);
    $query = \Drupal::entityQuery($entity_type);

    $query->condition($keys['bundle'], $bundle);
    $query->condition('langcode', $langcode);
    //$query->sortAggregate('id','RAND');
    $query->range(0, $url_to_fetch_count);
    $entity_ids = $query->execute();
    $results = [];
    $language = \Drupal::languageManager()->getLanguage($langcode);
    foreach ($entity_ids as $entity_id => $x) {
      $url = Url::fromRoute('entity.' . $entity_type . '.canonical', [$entity_type => $entity_id],  ['language' => $language]);
      $results [$entity_type . '_' . $langcode . '_' . $entity_id] = $url->toString();
    }

    return $results;
  }
}
