<?php

namespace Drupal\redis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Url;
use Drupal\redis\ClientFactory;
use Drupal\redis\RedisPrefixTrait;
use Predis\Collection\Iterator\Keyspace;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Redis Report page.
 *
 * Display status and statistics about the Redis connection.
 */
class ReportController extends ControllerBase {

  use RedisPrefixTrait;

  /**
   * The redis client.
   *
   * @var \Redis|\Predis\Client|false
   */
  protected $redis;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * ReportController constructor.
   *
   * @param \Drupal\redis\ClientFactory $client_factory
   *   The client factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(ClientFactory $client_factory, DateFormatterInterface $date_formatter) {
    if (ClientFactory::hasClient()) {
      $this->redis = $client_factory->getClient();
    }
    else {
      $this->redis = FALSE;
    }

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('redis.factory'), $container->get('date.formatter'));
  }

  /**
   * Redis report overview.
   */
  public function overview() {

    $build['report'] = [
      '#type' => 'status_report',
      '#requirements' => [],
    ];

    if ($this->redis === FALSE) {

      $build['report']['#requirements'] = [
        'client' => [
          'title' => 'Redis',
          'value' => t('Not connected.'),
          'severity_status' => 'error',
          'description' => t('No Redis client connected. Verify cache settings.'),
        ],
      ];

      return $build;
    }

    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    $start = microtime(TRUE);

    $info = $this->redis->info();

    $prefix_length = strlen($this->getPrefix()) + 1;

    $entries_per_bin = array_fill_keys(\Drupal::getContainer()->getParameter('cache_bins'), 0);

    $required_cached_contexts = \Drupal::getContainer()->getParameter('renderer.config')['required_cache_contexts'];

    $render_cache_totals = [];
    $render_cache_contexts = [];
    $cache_tags = [];
    $i = 0;
    $cache_tags_max = FALSE;
    foreach ($this->scan($this->getPrefix() . '*') as $key) {
      $i++;
      $second_colon_pos = mb_strpos($key, ':', $prefix_length);
      if ($second_colon_pos !== FALSE) {
        $bin = mb_substr($key, $prefix_length, $second_colon_pos - $prefix_length);
        if (isset($entries_per_bin[$bin])) {
          $entries_per_bin[$bin]++;
        }

        if ($bin == 'render') {
          $cache_key = mb_substr($key, $second_colon_pos + 1);

          $first_context = mb_strpos($cache_key, '[');
          if ($first_context) {
            $cache_key_only = mb_substr($cache_key, 0, $first_context - 1);
            if (!isset($render_cache_totals[$cache_key_only])) {
              $render_cache_totals[$cache_key_only] = 1;
            }
            else {
              $render_cache_totals[$cache_key_only]++;
            }

            if (preg_match_all('/\[([a-z0-9:_.]+)\]=([^:]*)/', $cache_key, $matches)) {
              foreach ($matches[1] as $index => $context) {
                $render_cache_contexts[$cache_key_only][$context][$matches[2][$index]] = $matches[2][$index];
              }
            }
          }
        }
        elseif ($bin == 'cachetags') {
          $cache_tag = mb_substr($key, $second_colon_pos + 1);
          // @todo: Make the max configurable or allow ot override it through
          // a query parameter.
          if (count($cache_tags) < 50000) {
            $cache_tags[$cache_tag] = $this->redis->get($key);
          }
          else {
            $cache_tags_max = TRUE;
          }
        }
      }

      // Do not process more than 100k cache keys.
      // @todo Adjust this after more testing or move to a separate page.
    }

    arsort($entries_per_bin);
    arsort($render_cache_totals);
    arsort($cache_tags);

    $per_bin_string = '';
    foreach ($entries_per_bin as $bin => $entries) {
      $per_bin_string .= "$bin: $entries<br />";
    }

    $render_cache_string = '';
    foreach (array_slice($render_cache_totals, 0, 50) as $cache_key => $total) {
      $contexts = implode(', ', array_diff(array_keys($render_cache_contexts[$cache_key]), $required_cached_contexts));
      $render_cache_string .= $contexts ? "$cache_key: $total ($contexts)<br />" : "$cache_key: $total<br />";
    }

    $cache_tags_string = '';
    foreach (array_slice($cache_tags, 0, 50) as $cache_tag => $invalidations) {
      $cache_tags_string .= "$cache_tag: $invalidations<br />";
    }

    $end = microtime(TRUE);
    $memory_config = $this->redis->config('get', 'maxmemory*');

    if ($memory_config['maxmemory']) {
      $memory_value = $this->t('@used_memory / @max_memory (@used_percentage%), maxmemory policy: @policy', [
        '@used_memory' => $info['used_memory_human'] ?? $info['Memory']['used_memory_human'],
        '@max_memory' => format_size($memory_config['maxmemory']),
        '@used_percentage' => (int) ($info['used_memory'] ?? $info['Memory']['used_memory'] / $memory_config['maxmemory'] * 100),
        '@policy' => $memory_config['maxmemory-policy'],
      ]);
    }
    else {
      $memory_value = $this->t('@used_memory / unlimited, maxmemory policy: @policy', [
        '@used_memory' => $info['used_memory_human'] ?? $info['Memory']['used_memory_human'],
        '@policy' => $memory_config['maxmemory-policy'],
      ]);
    }

    $requirements = [
      'client' => [
        'title' => $this->t('Client'),
        'value' => t("Connected, using the <em>@name</em> client.", ['@name' => ClientFactory::getClientName()]),
      ],
      'version' => [
        'title' => $this->t('Version'),
        'value' => $info['redis_version'] ?? $info['Server']['redis_version'],
      ],
      'clients' => [
        'title' => $this->t('Connected clients'),
        'value' => $info['connected_clients'] ?? $info['Clients']['connected_clients'],
      ],
      'dbsize' => [
        'title' => $this->t('Keys'),
        'value' => $this->redis->dbSize(),
      ],
      'memory' => [
        'title' => $this->t('Memory'),
        'value' => $memory_value,
      ],
      'uptime' => [
        'title' => $this->t('Uptime'),
        'value' => $this->dateFormatter->formatInterval($info['uptime_in_seconds'] ?? $info['Server']['uptime_in_seconds']),
      ],
      'read_write' => [
        'title' => $this->t('Read/Write'),
        'value' => $this->t('@read read (@percent_read%), @write written (@percent_write%), @commands commands in @connections connections.', [
          '@read' => format_size($info['total_net_output_bytes'] ?? $info['Stats']['total_net_output_bytes']),
          '@percent_read' => round(100 / (($info['total_net_output_bytes'] ?? $info['Stats']['total_net_output_bytes']) + ($info['total_net_input_bytes'] ?? $info['Stats']['total_net_input_bytes'])) * ($info['total_net_output_bytes'] ?? $info['Stats']['total_net_output_bytes'])),
          '@write' => format_size($info['total_net_input_bytes'] ?? $info['Stats']['total_net_input_bytes']),
          '@percent_write' => round(100 / (($info['total_net_output_bytes'] ?? $info['Stats']['total_net_output_bytes']) + ($info['total_net_input_bytes'] ?? $info['Stats']['total_net_input_bytes'])) * ($info['total_net_input_bytes'] ?? $info['Stats']['total_net_input_bytes'])),
          '@commands' => $info['total_commands_processed'] ?? $info['Stats']['total_commands_processed'],
          '@connections' => $info['total_connections_received'] ?? $info['Stats']['total_connections_received'],
        ]),
      ],
      'per_bin' => [
        'title' => $this->t('Keys per cache bin'),
        'value' => ['#markup' => $per_bin_string],
      ],
      'render_cache' => [
        'title' => $this->t('Render cache entries with most variations'),
        'value' => ['#markup' => $render_cache_string],
      ],
      'cache_tags' => [
        'title' => $this->t('Most invalidated cache tags'),
        'value' => ['#markup' => $cache_tags_string],
      ],
      'cache_tag_totals' => [
        'title' => $this->t('Total cache tag invalidations'),
        'value' => [
          '#markup' => $this->t('@count tags with @invalidations invalidations.', [
            '@count' => count($cache_tags),
            '@invalidations' => array_sum($cache_tags),
          ]),
        ],
      ],
      'time_spent' => [
        'title' => $this->t('Time spent'),
        'value' => ['#markup' => $this->t('@count keys in @time seconds.', ['@count' => $i, '@time' => round(($end - $start), 4)])],
      ],
    ];

    // Warnings/hints.
    if ($memory_config['maxmemory-policy'] == 'noeviction') {
      $redis_url = Url::fromUri('https://redis.io/topics/lru-cache', [
        'fragment' => 'eviction-policies',
        'attributes' => [
          'target' => '_blank',
        ],
      ]);
      $requirements['memory']['severity_status'] = 'warning';
      $requirements['memory']['description'] = $this->t('It is recommended to configure the maxmemory policy to e.g. volatile-lru, see <a href=":documentation_url">Redis documentation</a>.', [
        ':documentation_url' => $redis_url->toString(),
      ]);
    }
    if (count($cache_tags) == 0) {
      $requirements['cache_tag_totals']['severity_status'] = 'warning';
      $requirements['cache_tag_totals']['description'] = $this->t('No cache tags found, make sure that the redis cache tag checksum service is used. See example.services.yml on root of this module.');
      unset($requirements['cache_tags']);
    }

    if ($cache_tags_max) {
      $requirements['max_cache_tags'] = [
        'severity_status' => 'warning',
        'title' => $this->t('Cache tags limit reached'),
        'value' => ['#markup' => $this->t('Cache tag count incomplete, only counted @count cache tags.', ['@count' => count($cache_tags)])],
      ];
    }

    $build['report']['#requirements'] = $requirements;

    return $build;
  }

  /**
   * Wrapper to SCAN through matching redis keys.
   *
   * @param string $match
   *   The MATCH pattern.
   * @param int $count
   *   Count of keys per iteration (only a suggestion to Redis).
   *
   * @return \Generator
   */
  protected function scan($match, $count = 10000) {
    $it = NULL;
    if ($this->redis instanceof \Redis) {
      while ($keys = $this->redis->scan($it, $this->getPrefix() . '*', $count)) {
        yield from $keys;
      }
    }
    elseif ($this->redis instanceof \Predis\Client) {
      yield from new Keyspace($this->redis, $match, $count);
    }
  }

}
