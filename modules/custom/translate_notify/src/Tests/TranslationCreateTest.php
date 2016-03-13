<?php
/**
 * @file
 * Contains \Drupal\translate_notify\Tests\TranslationCreateTest.
 */

namespace Drupal\translate_notify\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;

/**
 * Tests that mail is sent on translation creation.
 *
 * @group content_translation
 */
class TranslationCreateTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'translate_notify',
    'content_translation',
    'language',
    'path',
    'node',
    'user',
    'system'
  ];

  /**
   * The mock mail manager.
   *
   * @var MailManagerInterface $mailManager
   */
  var $mailManager;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Save the definition of path_processor_alias, since we want to have
    // URL aliases.
    $alias = clone $container->getDefinition('path_processor_alias');
    parent::register($container);
    $container->setDefinition('path_processor_alias', $alias);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // We need paths and nodes to actually exist.
    $this->installSchema('system', ['url_alias', 'router']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->container->get('router.builder')->rebuild();

    // Setup a language.
    ConfigurableLanguage::create(['id' => 'fr', 'label' => 'French'])->save();

    // Mock the mail manager.
    $this->mailManager = $this->getMockBuilder('\Drupal\Core\Mail\MailManagerInterface')->getMock();
    $this->container->set('plugin.manager.mail', $this->mailManager);
  }

  /**
   * Test mail being sent on translation creation.
   */
  public function testTranslationCreate() {
    // Create a node.
    $node = Node::create(['type' => 'test', 'title' => 'foo', 'path' => '/bar']);
    $node->save();

    // Store the params of any call to mail.
    $mail_params = NULL;
    $this->mailManager->method('mail')->will($this->returnCallback(
      function($module, $key, $to, $langcode, $params = array(), $reply = NULL, $send = TRUE) use (&$mail_params) {
        $mail_params = $params;
      }
    ));

    // Expect something to be mailed when we add a translation.
    $this->mailManager->expects($this->once())
      ->method('mail');

    // Add a translation.
    $node->addTranslation('fr');

    // Check that the parameters sent to mail look ok.
    $message = $mail_params['context']['message']->render();
    $this->assertContains('/bar', $message, "Body contains path");
    $this->assertContains('foo', $message, "Body contains title");
  }

}
