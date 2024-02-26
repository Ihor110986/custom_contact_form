<?php

namespace Drupal\custom_contact_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

class CustomContactForm extends FormBase {
  protected $database;

  // Constructor to inject the database service.
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  // Inject the database service via the constructor.
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_contact_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitFormAjax',
      ],
    ];

    // Container to display the success message.
    $form['message_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'custom-contact-form-message'],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method is intentionally left empty because we're handling form submission using AJAX.
  }

  /**
   * Ajax callback to submit the form.
   */
  public function submitFormAjax(array &$form, FormStateInterface $form_state) {
    // Get the submitted values from the form.
    $name = $form_state->getValue('name');
    $email = $form_state->getValue('email');
    $message = $form_state->getValue('message');

    // Check if all fields contain at least one character.
    if (empty($name) || empty($email) || empty($message)) {
      $response = new AjaxResponse();
      $response->addCommand(
        new HtmlCommand(
          '#custom-contact-form-message',
          '<div class="alert alert-danger">All fields are required.</div>'
        )
      );
      return $response;
    }

    // Check if email field contains '@' symbol.
    if (strpos($email, '@') === false) {
      $response = new AjaxResponse();
      $response->addCommand(
        new HtmlCommand(
          '#custom-contact-form-message',
          '<div class="alert alert-danger">Please enter a valid email address.</div>'
        )
      );
      return $response;
    }

    // Save the submitted values into the database.
    $this->database->insert('custom_contact_form_data')
      ->fields([
        'name' => $name,
        'email' => $email,
        'message' => $message,
      ])
      ->execute();

    // Build the Ajax response to display the success message.
    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '#custom-contact-form-message',
        '<div class="alert alert-success">Your form has been submitted successfully. Thank you!</div>'
      )
    );

    return $response;
  }
}