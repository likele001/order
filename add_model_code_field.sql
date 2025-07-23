-- 为scanwork_model表添加型号编号字段
ALTER TABLE scanwork_model ADD COLUMN model_code VARCHAR(50) NOT NULL DEFAULT '' COMMENT '型号编号' AFTER name;

-- 为现有数据添加示例编号（可选）
-- UPDATE scanwork_model SET model_code = CONCAT('M', LPAD(id, 4, '0')) WHERE model_code = ''; 