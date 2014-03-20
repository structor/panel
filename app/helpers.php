<?php 

function layout($file, $data = array()) {
  return new Layout($file, $data);
}

function view($file, $data = array()) {
  return new View($file, $data);
}

function blueprint($page) {
  return $page->blueprint();
}

function fileResponse($file, $child = false) {

  $result = array(
    'filename'  => $file->filename(),
    'name'      => $file->name(),
    'extension' => $file->extension(),
    'size'      => $file->niceSize(),
    'type'      => $file->type(),
    'url'       => $file->url()
  );

  if(!$child) {

    $result['prev'] = $file->prev() ? fileResponse($file->prev(), true) : false;
    $result['next'] = $file->next() ? fileResponse($file->next(), true) : false;
    $result['meta'] = array_map(function($field) {
      return (string)$field;
    }, $file->meta()->data());

    if(empty($result['meta'])) $result['meta'] = null;

  }

  return $result;

}

function pageResponse($page, $child = false) {

  $result = array(
    'title'    => (string)$page->title(),
    'url'      => $page->url(),
    'uri'      => $page->uri(),
    'uid'      => $page->uid(),
    'slug'     => $page->slug(),
    'num'      => intval($page->num()), 
    'home'     => $page->isHomePage(),
    'error'    => $page->isErrorPage(),
    'visible'  => $page->isVisible(),
    'template' => $page->template()
  );

  // if there's a blueprint for the intended page template, use that!
  if(c::get('root.blueprints') . DS . $page->intendedTemplate() . '.php') {
    $result['template'] = $page->intendedTemplate();
  }

  if(!$page->isSite() and !$child) {
    $result['parent']  = pageResponse($page->parent(), true);    
    $result['parents'] = $page->parents()->toArray(function($item) {
      return pageResponse($item, true);
    });
  }

  if(!$child) $result['children'] = array_values($page->children()->toArray(function($item) {
      return pageResponse($item, true);
  }));

  if(!$child) $result['content'] = array_map(function($field) {
    return (string)$field;
  }, $page->content()->data());

  if(!$child) $result['files'] = array_values($page->files()->toArray(function($file) {
    return fileResponse($file);
  }));

  $result['deletable'] = array(
    'status'  => true,
    'message' => 'This page can be deleted'
  );

  $result['changeableURL'] = array(
    'status'  => true,
    'message' => 'The URL for this page can be changed'
  );

  // deletable status
  if($page->isErrorPage()) {
    $result['deletable'] = array(
      'status'  => false,
      'message' => 'The error page cannot be deleted'
    );
    $result['changeableURL'] = array(
      'status'  => false,
      'message' => 'The URL for the error page cannot be changed'
    );
  }

  if($page->isHomePage()) {
    $result['deletable'] = array(
      'status'  => false,
      'message' => 'The home page cannot be deleted'
    );
    $result['changeableURL'] = array(
      'status'  => false,
      'message' => 'The URL for the home page cannot be changed'
    );
  }

  if($page->hasChildren()) {
    $result['deletable'] = array(
      'status'  => false,
      'message' => 'This page has subpages. Please delete them first'
    );
  }

  if(!$child) {

    $blueprint = $page->blueprint();

    $result['settings'] = array(
      'pages' => $blueprint->pages(),
      'files' => $blueprint->files()
    );

  }

  return $result;

}


function fetchPage($site, $uri) {
  if(empty($uri)) return false;
  return $site->find($uri);
}