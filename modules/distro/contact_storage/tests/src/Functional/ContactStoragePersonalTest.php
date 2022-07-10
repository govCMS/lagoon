<?php

namespace Drupal\Tests\contact_storage\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests personal contact form functionality.
 *
 * @group contact
 */
class ContactStoragePersonalTest extends BrowserTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['contact', 'contact_storage', 'dblog'];

  /**
   * A user with some administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  private $adminUser;

  /**
   * A user with permission to view profiles and access user contact forms.
   *
   * @var \Drupal\user\UserInterface
   */
  private $webUser;

  /**
   * A user without any permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  private $contactUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser(['administer contact forms', 'administer users', 'administer account settings', 'access site reports']);

    // Create some normal users with their contact forms enabled by default.
    $this->config('contact.settings')->set('user_default_enabled', TRUE)->save();
    $this->webUser = $this->drupalCreateUser(['access user profiles', 'access user contact forms']);
    $this->contactUser = $this->drupalCreateUser();
  }

  /**
   * Tests that mails for contact messages are correctly sent.
   */
  public function testSendPersonalContactMessage() {
    // Ensure that the web user's email needs escaping.
    $mail = $this->webUser->getAccountName() . '&escaped@example.com';
    $this->webUser->setEmail($mail)->save();
    $this->drupalLogin($this->webUser);

    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertEscaped($mail);
    $message = $this->submitPersonalContact($this->contactUser);
    $mails = $this->drupalGetMails();
    $this->assertEqual(1, count($mails));
    $mail = $mails[0];
    $this->assertEqual($mail['to'], $this->contactUser->getEmail());
    $this->assertEqual($mail['from'], $this->config('system.site')->get('mail'));
    $this->assertEqual($mail['reply-to'], $this->webUser->getEmail());
    $this->assertEqual($mail['key'], 'user_mail');
    $variables = [
      '@site-name' => $this->config('system.site')->get('name'),
      '@subject' => $message['subject[0][value]'],
      '@recipient-name' => $this->contactUser->getDisplayName(),
    ];
    $subject = PlainTextOutput::renderFromHtml(t('[@site-name] @subject', $variables));
    $this->assertEqual($mail['subject'], $subject, 'Subject is in sent message.');
    $this->assertTrue(strpos($mail['body'], 'Hello ' . $variables['@recipient-name']) !== FALSE, 'Recipient name is in sent message.');
    $this->assertTrue(strpos($mail['body'], $this->webUser->getDisplayName()) !== FALSE, 'Sender name is in sent message.');
    $this->assertTrue(strpos($mail['body'], $message['message[0][value]']) !== FALSE, 'Message body is in sent message.');

    // Check there was no problems raised during sending.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    // Verify that the correct watchdog message has been logged.
    $this->drupalGet('/admin/reports/dblog');
    $placeholders = [
      '@sender_name' => $this->webUser->username,
      '@sender_email' => $this->webUser->getEmail(),
      '@recipient_name' => $this->contactUser->getDisplayName(),
    ];
    $this->assertRaw(new FormattableMarkup('@sender_name (@sender_email) sent @recipient_name an email.', $placeholders));
    // Ensure an unescaped version of the email does not exist anywhere.
    $this->assertNoRaw($this->webUser->getEmail());
  }

  /**
   * Fills out a user's personal contact form and submits it.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object of the user being contacted.
   * @param array $message
   *   (optional) An array with the form fields being used. Defaults to an empty
   *   array.
   *
   * @return array
   *   An array with the form fields being used.
   */
  protected function submitPersonalContact(AccountInterface $account, array $message = []) {
    $message += [
      'subject[0][value]' => $this->randomMachineName(16),
      'message[0][value]' => $this->randomMachineName(64),
    ];
    $this->drupalPostForm('user/' . $account->id() . '/contact', $message, t('Send message'));
    return $message;
  }

}
