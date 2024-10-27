<?php

namespace Drupal\tcg_crm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class TcgCrmController extends ControllerBase {

  protected FileSystemInterface $fileSystem;

  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system')
    );
  }

  public static function request() {
    return static::getContainer()->get('request_stack')
      ->getCurrentRequest();
  }

  public function add(Request $request) {
    $name = $request->request->get('name');
    $email = $request->request->get('email');
    $message = $request->request->get('message');
    $file = $request->files->get('file');

    if ($file) {
      $filename = $this->fileSystem->move($file, 'public://');
      $filePath = \Drupal::service('file_system')->realpath($filename);
    } else {
      $filePath = '';
    }

    $query = \Drupal::database()->insert('tcg_submission')
      ->fields(['name', 'email', 'message', 'image_url'])
      ->values([$name, $email, $message, $filePath])
      ->execute();

    if ($query) {
      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Submission successful',
      ]);
    } else {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Submission failed',
      ]);
    }
  }

  public function get($sid) {
    $query = \Drupal::database()->select('tcg_submission')
      ->fields('tcg_submission')
      ->condition('id', $sid);
    $result = $query->execute()->fetchObject();

    if ($result) {
      $data = [
        'id' => $result->id,
        'name' => $result->name,
        'email' => $result->email,
        'message' => $result->message,
        'image_url' => $result->image_url,
      ];

      return new JsonResponse($data);
    } else {
      return new JsonResponse(['error' => 'Submission not found'], 404);
    }
  }

  public function list() {
    $query = \Drupal::database()->select('tcg_submission')
      ->fields('tcg_submission');
    $results = $query->execute()->fetchAll();

    $data = [];
    foreach ($results as $result) {
      $data[] = [
        'id' => $result->id,
        'name' => $result->name,
        'email' => $result->email,
        'message' => $result->message,
        'image_url' => $result->image_url,
      ];
    }

    return new JsonResponse($data);
  }

  public function update($sid, Request $request) {
    $name = $request->request->get('name');
    $email = $request->request->get('email');
    $message = $request->request->get('message');
    $file = $request->files->get('file');

    if ($file) {
      $filename = $this->fileSystem->move($file, 'public://');
      $filePath = \Drupal::service('file_system')->realpath($filename);
    } else {
      $filePath = '';
    }

    $query = \Drupal::database()->update('tcg_submission')
      ->fields([
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'image_url' => $filePath, // Use the updated image path
      ])
      ->condition('id', $sid)
      ->execute();

    if ($query) {
      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Submission updated successfully',
      ]);
    } else {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Submission update failed',
      ]);
    }
  }

  public function dashboard() {
    $query = \Drupal::database()->select('tcg_submission')
      ->fields('tcg_submission');
    $result = $query->execute()->fetchAll();

    $build = [
      '#title' => $this->t('Sample Submissions'),
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Message'),
        $this->t('Image URL'),
      ],
      '#rows' => [],
    ];

    foreach ($result as $row) {
      $build['#rows'][] = [
        $row->id,
        $row->name,
        $row->email,
        $row->message,
        $row->image_url,
      ];
    }

    return $build;
  }

}
