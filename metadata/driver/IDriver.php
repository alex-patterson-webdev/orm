<?php

namespace Orm\Metadata\Driver;

interface IDriver
{
  public function getAllEntityNames();

  public function getEntityMetadata($entityName);

  public function getFieldMetadata($entityName);

  public function getAssociationMetadata($entityName);
}