<?php

namespace Intersect\Database\Response;

use Intersect\Core\Http\Response;
use Intersect\Database\Model\Model;
use Intersect\Core\Http\ResponseHandler;
use Intersect\Database\Model\ModelHelper;

class ModelResponseHandler implements ResponseHandler {

    public function canHandle(Response $response)
    {
        return ($response->getBody() instanceof Model);
    }

    /**
     * @param Response $response
     */
    public function handle(Response $response)
    {
        /** @var Model $model */
        $model = $response->getBody();
        $relationships = $model->getRelationships();

        foreach ($relationships as $key => &$relationship)
        {
            if (is_array($relationship))
            {
                $relationship = ModelHelper::normalizeList($relationship);
            }
            else if ($relationship instanceof Model)
            {
                $relationship = ModelHelper::normalize($relationship);
            }
        }

        $modelData = [
            'class' => get_class($model),
            'database' => [
                'table' => $model->getTableName(),
                'primaryKey' => $model->getPrimaryKey(),
                'columns' => $model->getColumnList(),
            ],
            'readonlyAttributes' => $model->getReadOnlyAttributes(),
            'attributes' => $model->getAttributes(),
            'relationships' => $relationships
        ];

        header('Content-Type: application/json');
        echo json_encode($modelData);
    }

}