<?php

namespace Drupal\pt_content_feedback\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Provides a 'ContentFeedbackBlock' block.
 *
 * @Block(
 *  id = "pt_content_feedback",
 *  admin_label = @Translation("Content Feedback"),
 *  context = {
 *    "node" = @ContextDefinition(
 *      "entity:node",
 *      label = @Translation("Current Node")
 *    )
 *  }
 * )
 */
class ContentFeedbackBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Symfony\Component\HttpFoundation\Session\Session session.
   *
   * @var Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * Constructs a new ContentFeedbackBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param Symfony\Component\HttpFoundation\Session\Session $session
   *   The current session.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Session $session
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    $build = [
      '#theme' => 'pt_content_feedback',
      '#nid' => $node->id(),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $node = $this->getContextValue('node');

    $contentScored = $this->session->get('content_scored', []);
    return AccessResult::allowedIf(!in_array($node->id(), $contentScored));
  }

}
