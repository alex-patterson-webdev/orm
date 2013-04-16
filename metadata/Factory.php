<?php

namespace Orm\Metadata;

use Orm\Database\Adapter;
use Orm\Metadata\Driver as Driver;

class Factory
{
  /**
   * $_driver
   *
   * @var Orm\Metadata\Driver
   */
  protected $_driver = null;

  /**
   * __construct
   *
   * @param Database\Adapter $dbAdapter      [description]
   * @param Driver\IDriver   $metadataDriver [description]
   */
  public function __construct(Driver\IDriver $driver)
  {
    $this->_driver = $driver;
    $this->_names = $driver->getAllEntityNames();

    return $this;
  }

  /**
   * hasMetadataFor
   *
   * Check if this entity metadata has been defined
   * 
   * @param  [type]  $entityName [description]
   * @return boolean             [description]
   */
  protected function hasMetadataFor($entityName)
  {
    return (isset($this->_names[$entityName])) ? true : false;
  }

  /**
   * getEntityMetadata
   *
   * Return the entity metadata instance for a given entity name
   * The instance will be create is not already
   * 
   * @param  [type] $entityName [description]
   * @return [type]             [description]
   */
  public function getEntityMetadata($entityName)
  {
    if (! isset($this->_metadata[$entityName])) {
      $this->_metadata[$entityName] = $this->loadEntityMetadata($entityName);
    }
    return $this->_metadata[$entityName];
  }

  /**
   * newMetadata
   *
   * Return a new metadata instance
   * 
   * @param  [type] $className [description]
   * @param  array  $options   [description]
   * @return [type]            [description]
   */
  public function newMetadata($className, array $metadata = array())
  {
    return new Metadata($className, $metadata);
  }

  /**
   * loadEntityMetadata
   *
   * Load the entity metadata for a given entity name
   * 
   * @param  [type] $entityName [description]
   * @return [type]             [description]
   */
  protected function loadEntityMetadata($entityName)
  {
    if (! $this->hasMetadataFor($entityName)) {
      throw new \InvalidArgumentException('Cannot fetch metadata for unknown entity ' . $entityName);
    }
    $metadata = $this->_driver->populate($this, $entityName);



    if (! empty($data)) {
      $data = array(
        'entityName' => $data['entityName'],
        'className'  => $data['className'],
        'tableName' => $data['tableName'],
        'repositoryClass' => $data[''],
      );
      $metadata = new Metadata($data['className'], )


    $data = $this->_driver->getEntityMetadata($entityName);





    /** Entity Metadata **/
      if (isQuery(metadata) && metadata.recordCount == 1) {
        variables.entityName  = metadata["entity_name"][1];
        variables.tableName   = metadata["table_name"][1];
        variables.className   = metadata["class_name"][1];
        variables.repository  = metadata["repository_class_name"][1];
        //variables.primaryKeys = listToArray(metadata["primary_keys"][1]);

        var mappings = getFieldMetadata();
        if (isQuery(mappings) && mappings.recordCount) {
          for (var x = 1; x <= mappings.recordCount; x++) {

            /** Create the field mapping **/
            var fieldMapping = {
              fieldName    = mappings["field_name"][x],
              columnName   = mappings["column_name"][x],
              identity     = (val(mappings["is_identity"][x]) ? true : false),
              dataType     = mappings["data_type"][x],
              dataLength   = mappings["data_length"][x],
              defaultValue = mappings["default_value"][x],
              isIndex      = mappings["is_index"][x]
              //isNullable   = mappings["is_nullable"][x]
            };
            addFieldMapping(fieldMapping);
          }
        }
      }
      var relationships = getRelationshipMetadata();
      if (isQuery(relationships) && relationships.recordCount) {
        for (var y = 1; y <= relationships.recordCount; y++) {
          var mapping = {
            type              = relationships["relationship_type"][y],
            fieldName         = relationships["field_name"][y],
            identity          = (val(relationships["is_identity"][y]) ? true : false),
            sourceEntityName  = relationships["entity_name"][y],
            targetEntityName  = relationships["target_entity_name"][y],
            mappedByColumn    = relationships["mapped_by_column_name"][y],
            inversedByColumn  = relationships["inversed_by_column_name"][y],
            loadType          = relationships["load_type"][y],
            joinColumns       = listToArray(relationships["join_column_names"][y]),
            referencedColumns = listToArray(relationships["referenced_column_names"][y]),
            joinTable         = {
              name = relationships["join_table_name"][y],
              joinColumns        = [],
              inverseJoinColumns = []
            }
          };

          /** Validate and complete the assoc mapping **/
          switch (mapping.type) {
            case "ONETOONE":
              mapping = addOneToOneMapping(mapping);
            break;
            case "OneToMany":
              //mapping = getOneToManyMapping(mapping);
            break;
            case "ManyToOne":
              //mapping = getManyToOneMapping(mapping);
            break;
            case "ManyToMany":
              mapping = addManyToManyMapping(mapping);
            break;
          }
        }
      }

  }



}