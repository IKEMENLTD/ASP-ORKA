-- Fix HEAD_DESIGN and FOOT_DESIGN templates for nUser registration
-- These templates are required by System::getHead() and System::getFoot()

-- Check existing HEAD/FOOT templates
SELECT id, label, user_type, target_type, file FROM template
WHERE label LIKE '%HEAD%' OR label LIKE '%FOOT%'
ORDER BY label;

-- Insert HEAD_DESIGN template for nobody user (if not exists)
INSERT INTO template (
    label,
    user_type,
    target_type,
    owner,
    activate,
    file
)
SELECT
    'HEAD_DESIGN',
    '/nobody/',
    '',
    '2',
    '15',
    'base/Head.html'
WHERE NOT EXISTS (
    SELECT 1 FROM template
    WHERE label = 'HEAD_DESIGN' AND user_type LIKE '%nobody%'
);

-- Insert FOOT_DESIGN template for nobody user (if not exists)
INSERT INTO template (
    label,
    user_type,
    target_type,
    owner,
    activate,
    file
)
SELECT
    'FOOT_DESIGN',
    '/nobody/',
    '',
    '2',
    '15',
    'base/Foot.html'
WHERE NOT EXISTS (
    SELECT 1 FROM template
    WHERE label = 'FOOT_DESIGN' AND user_type LIKE '%nobody%'
);

-- Verify inserts
SELECT id, label, user_type, target_type, owner, activate, file
FROM template
WHERE label IN ('HEAD_DESIGN', 'FOOT_DESIGN')
ORDER BY label;
