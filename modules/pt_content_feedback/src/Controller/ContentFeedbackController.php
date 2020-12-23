<?php

namespace Drupal\pt_content_feedback\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\pt_content_feedback\Ajax\ContentFeedbackCommand;
use Drupal\pt_content_feedback\ContentFeedback;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ContentFeedbackController.
 */
class ContentFeedbackController extends ControllerBase {

  private const MODULE_NAME = 'pt_content_feedback';
  private const PAGER_LIMIT = 15;

  /**
   * Drupal\Core\Database\Connection connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Symfony\Component\HttpFoundation\Session\SessionInterface session.
   *
   * @var Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface moduleHandler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface entityTypeManager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Routing\UrlGeneratorInterface urlGenerator.
   *
   * @var Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Database\Connection $connection
   *   Database connection.
   * @param Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Session.
   * @param Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   Url generator.
   */
  public function __construct(
    Connection $connection,
    SessionInterface $session,
    ModuleHandlerInterface $moduleHandler,
    EntityTypeManagerInterface $entityTypeManager,
    UrlGeneratorInterface $urlGenerator
  ) {
    $this->connection = $connection;
    $this->session = $session;
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('session'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('url_generator')
    );
  }

  /**
   * Save content score.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Return an ajax response.
   */
  public function store($entityId, $score) {
    $result = TRUE;

    try {
      if ($this->hasBeenScored($entityId)) {
        throw new \RuntimeException('This content has been scored by this user already');
      }

      if (!$this->scoreIsValid($score)) {
        throw new \RuntimeException('This type of feedback is not supported');
      }

      $this->connection->update(ContentFeedback::TABLE_NAME)
        ->expression($score, $score . ' + 1')
        ->condition('entity_id', $entityId, '=')
        ->execute();

      $contentScored = $this->session->get('content_scored', []);
      $contentScored[] = $entityId;
      $this->session->set('content_scored', $contentScored);
    }
    catch (\Throwable $e) {
      $result = FALSE;
    }

    $response = new AjaxResponse();
    $response->addCommand(new ContentFeedbackCommand($result));

    return $response;
  }

  /**
   * Node feedback detail.
   *
   * @return array
   *   Return a Render Array.
   *
   * @throws NotFoundHttpException
   */
  public function show($node) {
    try {
      $data = $this->connection->select(ContentFeedback::TABLE_NAME, 'cf')
        ->fields('cf', ContentFeedback::SCORES)
        ->condition('entity_id', $node, '=')
        ->execute()
        ->fetchAssoc();

      $content = [
        'container' => [
          '#type' => 'container',
          '#attached' => [
            'library' => [
              self::MODULE_NAME . '/pt_content_feedback-detail',
            ],
          ],
        ],
      ];

      $path = $this->moduleHandler->getModule(self::MODULE_NAME)->getPath();

      foreach ($data as $title => $value) {
        $content['container'][$title] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          [
            '#type' => 'html_tag',
            '#tag' => 'img',
            '#attributes' => [
              'src' => file_create_url($path) . '/img/' . $title . '.png',
            ],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => ': ' . $value,
          ],
        ];
      }
    }
    catch (\Throwable $e) {
      throw new NotFoundHttpException($e->getMessage());
    }

    return $content;
  }

  /**
   * All content feedback's list.
   *
   * @return array
   *   Return a Render Array.
   */
  public function list() {
    $header = $this->buildTableHeader();

    $query = $this->connection->select(ContentFeedback::TABLE_NAME, 'cf');
    $query->fields('cf');
    $pager = $query->extend('\Drupal\Core\Database\Query\PagerSelectExtender')->limit(self::PAGER_LIMIT);
    $sorter = $pager->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $data = $sorter->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $data = $this->addNodesTitle($data);

    $page = [
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $this->buildTableRows($data),
      ],
      'pager' => [
        '#type' => 'pager',
      ],
    ];

    return $page;
  }

  /**
   * Content has been scored by user.
   *
   * @param int $entityId
   *   Entity Id.
   *
   * @return bool
   *   Content has been scored.
   */
  private function hasBeenScored($entityId) {
    return isset($this->session->get('content_scored', [])[$entityId]);
  }

  /**
   * Score is valid.
   *
   * @param string $score
   *   Score.
   *
   * @return bool
   *   If scores is valid or not.
   */
  private function scoreIsValid($score) {
    return in_array($score, ContentFeedback::SCORES);
  }

  /**
   * Build table header.
   *
   * @return array
   *   Built table header.
   */
  private function buildTableHeader() {
    $scoreTranslations = [
      $this->t('Not Useful'),
      $this->t('Dont Care'),
      $this->t('Useful'),
      $this->t('Really Useful'),
    ];

    $header = [
      [
        'data' => $this->t('Node ID'),
        'field' => 'entity_id',
        'sort' => 'asc',
      ],
      [
        'data' => $this->t('Title'),
        'field' => 'title',
        'sort' => 'asc',
      ],
    ];

    foreach (ContentFeedback::SCORES as $key => $score) {
      $header[] = [
        'data' => $scoreTranslations[$key],
        'field' => $score,
        'sort' => 'asc',
      ];
    }

    return $header;
  }

  /**
   * Build table rows.
   *
   * @param array $data
   *   Data that should go in rows.
   *
   * @return array
   *   Built table rows.
   */
  private function buildTableRows($data) {
    return array_map([$this, 'buildRow'], $data);
  }

  /**
   * Build table row.
   *
   * @param array $row
   *   Data for a single row.
   *
   * @return array
   *   Built row.
   */
  private function buildRow($row) {
    $builtRow = ['entity_id' => $row['entity_id'], 'title' => $row['title']];
    foreach (ContentFeedback::SCORES as $score) {
      $builtRow[$score] = $row[$score];
    }
    return $builtRow;
  }

  /**
   * Add Node title to each row.
   *
   * @param array $data
   *   Data for each row.
   *
   * @return array
   *   Data with appended node title on each row.
   */
  private function addNodesTitle($data) {
    return array_map([$this, 'addNodeTitleRow'], $data);
  }

  /**
   * Add Node title to row.
   *
   * @param array $row
   *   Data for row.
   *
   * @return array
   *   Row with appended node title.
   */
  private function addNodeTitleRow($row) {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $node = $nodeStorage->load($row['entity_id']);

    $row['title'] = [
      'data' => new FormattableMarkup(
        '<a href=":link">@title</a>',
        [
          '@title' => $node->getTitle(),
          ':link' => $this->urlGenerator->generateFromRoute(
            'entity.node.canonical',
            ['node' => $node->id()],
            ['absolute' => TRUE]
          ),
        ]
      ),
    ];

    return $row;
  }

}
