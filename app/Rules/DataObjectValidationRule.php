<?php

namespace App\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Custom rule to ensure Data objects have validation on properties
 */
class DataObjectValidationRule implements \PHPStan\Rules\Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @param Scope $scope
     * @return array
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        if (!$class->isSubclassOf(\Spatie\LaravelData\Data::class)) {
            return [];
        }

        // Check if class has constructor parameters with validation
        $errors = [];
        $reflection = $class->getNativeReflection();
        $constructor = $reflection->getMethod('__construct');
        
        foreach ($constructor->getParameters() as $parameter) {
            $hasValidation = false;
            $attributes = $parameter->getAttributes();
            
            foreach ($attributes as $attribute) {
                if ($attribute->getName() === \Spatie\LaravelData\Attributes\Validation::class) {
                    $hasValidation = true;
                    break;
                }
            }
            
            // Skip validation for special parameters
            $paramName = $parameter->getName();
            if (!$hasValidation && 
                $paramName !== 'id' && 
                $paramName !== 'items' && 
                $paramName !== 'shippingAddress' && 
                $paramName !== 'meta' && 
                $paramName !== 'metaData' && 
                $paramName !== 'reasons' && 
                $paramName !== 'conditions' && 
                $paramName !== 'tags' && 
                $paramName !== 'imageUrls' && 
                $paramName !== 'eligibility' && 
                $paramName !== 'category' && 
                $paramName !== 'benefits') {
                $errors[] = RuleErrorBuilder::message(
                    sprintf('Parameter $%s in %s should have Validation attribute', $paramName, $class->getName())
                )->build();
            }
        }

        return $errors;
    }
}