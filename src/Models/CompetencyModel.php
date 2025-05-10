<?php

namespace App\Models;

use App\Services\Database;

/**
 * CompetencyModel - Competency data model
 * 
 * This class handles database operations related to competencies and their categories.
 * It supports multilingual content through translation tables.
 */
class CompetencyModel
{
    private Database $database;
    
    /**
     * Create a new CompetencyModel instance
     * 
     * @param Database|null $database Database service
     */
    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? new Database();
    }
    
    /**
     * Get all competency categories with their competencies for a specific language
     * 
     * @param string $langCode Language code
     * @return array Categories with their competencies
     */
    public function getAllCategoriesWithCompetencies(string $langCode): array
    {
        // Get language ID
        $languageModel = new LanguageModel($this->database);
        $language = $languageModel->getLanguageByCode($langCode);
        
        if (!$language) {
            return [];
        }
        
        // First, get all categories
        $sql = 'SELECT cc.id, cc.slug, cc.icon, cct.name 
                FROM competency_categories cc
                JOIN competency_category_translations cct ON cc.id = cct.category_id 
                WHERE cct.language_id = :langId
                ORDER BY cc.id';
        
        $categories = $this->database->fetchAll($sql, ['langId' => $language->id]);
        
        // Now get competencies for each category
        $result = [];
        foreach ($categories as $category) {
            $sql = 'SELECT c.id, c.slug, c.color, ct.name 
                    FROM competencies c
                    JOIN competency_translations ct ON c.id = ct.competency_id
                    WHERE c.category_id = :categoryId AND ct.language_id = :langId
                    ORDER BY c.display_order, c.id';
            
            $competencies = $this->database->fetchAll($sql, [
                'categoryId' => $category->id,
                'langId' => $language->id
            ]);
            
            $result[] = [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'icon' => $category->icon,
                'competencies' => $competencies
            ];
        }
        
        return $result;
    }
    
    /**
     * Get a specific competency category with its competencies
     * 
     * @param string $categorySlug Category slug
     * @param string $langCode Language code
     * @return array|null Category with competencies or null if not found
     */
    public function getCategoryWithCompetencies(string $categorySlug, string $langCode): ?array
    {
        // Get language ID
        $languageModel = new LanguageModel($this->database);
        $language = $languageModel->getLanguageByCode($langCode);
        
        if (!$language) {
            return null;
        }
        
        // Get the category
        $sql = 'SELECT cc.id, cc.slug, cc.icon, cct.name 
                FROM competency_categories cc
                JOIN competency_category_translations cct ON cc.id = cct.category_id 
                WHERE cc.slug = :slug AND cct.language_id = :langId';
        
        $category = $this->database->fetchOne($sql, [
            'slug' => $categorySlug,
            'langId' => $language->id
        ]);
        
        if (!$category) {
            return null;
        }
        
        // Get competencies for this category
        $sql = 'SELECT c.id, c.slug, c.color, ct.name 
                FROM competencies c
                JOIN competency_translations ct ON c.id = ct.competency_id
                WHERE c.category_id = :categoryId AND ct.language_id = :langId
                ORDER BY c.display_order, c.id';
        
        $competencies = $this->database->fetchAll($sql, [
            'categoryId' => $category->id,
            'langId' => $language->id
        ]);
        
        return [
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->name,
            'icon' => $category->icon,
            'competencies' => $competencies
        ];
    }
    
    /**
     * Add a new competency category
     * 
     * @param string $slug Category slug
     * @param string $icon Category icon
     * @param array $translations Category name translations (language code => name)
     * @return int New category ID
     */
    public function addCategory(string $slug, string $icon, array $translations): int
    {
        try {
            $this->database->beginTransaction();
            
            // Insert category
            $categoryId = $this->database->insert('competency_categories', [
                'slug' => $slug,
                'icon' => $icon
            ]);
            
            // Insert translations
            $languageModel = new LanguageModel($this->database);
            
            foreach ($translations as $langCode => $name) {
                $language = $languageModel->getLanguageByCode($langCode);
                
                if (!$language) {
                    continue;
                }
                
                $this->database->insert('competency_category_translations', [
                    'category_id' => $categoryId,
                    'language_id' => $language->id,
                    'name' => $name
                ]);
            }
            
            $this->database->commit();
            return $categoryId;
        } catch (\Exception $e) {
            $this->database->rollback();
            throw $e;
        }
    }
    
    /**
     * Add a new competency
     * 
     * @param int $categoryId Category ID
     * @param string $slug Competency slug
     * @param string $color Badge color (HEX)
     * @param int $displayOrder Display order
     * @param array $translations Competency name translations (language code => name)
     * @return int New competency ID
     */
    public function addCompetency(int $categoryId, string $slug, string $color, int $displayOrder, array $translations): int
    {
        try {
            $this->database->beginTransaction();
            
            // Insert competency
            $competencyId = $this->database->insert('competencies', [
                'category_id' => $categoryId,
                'slug' => $slug,
                'color' => $color,
                'display_order' => $displayOrder
            ]);
            
            // Insert translations
            $languageModel = new LanguageModel($this->database);
            
            foreach ($translations as $langCode => $name) {
                $language = $languageModel->getLanguageByCode($langCode);
                
                if (!$language) {
                    continue;
                }
                
                $this->database->insert('competency_translations', [
                    'competency_id' => $competencyId,
                    'language_id' => $language->id,
                    'name' => $name
                ]);
            }
            
            $this->database->commit();
            return $competencyId;
        } catch (\Exception $e) {
            $this->database->rollback();
            throw $e;
        }
    }
}