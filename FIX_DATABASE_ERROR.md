# 数据库错误修复说明

## 错误信息
```
SQLSTATE[42S22]： 未找到列： 1054 'where 子句'中的未知列 'r.employee_id'
```

## 问题原因
这个错误是因为在员工报工控制器中使用了 `r.employee_id` 字段，但是数据库中的报工表可能缺少这个字段，或者字段名称不匹配。

## 修复步骤

### 1. 执行数据库修复脚本
运行以下SQL脚本来修复数据库表结构：

```sql
-- 执行 database/fix_scanwork_tables.sql 文件中的内容
```

### 2. 检查报工表结构
确保 `fa_scanwork_report` 表包含以下字段：

```sql
DESCRIBE fa_scanwork_report;
```

应该包含以下字段：
- `id` - 主键
- `allocation_id` - 分配ID
- `employee_id` - 员工ID
- `model_id` - 型号ID
- `process_id` - 工序ID
- `quantity` - 报工数量
- `price` - 工价
- `wage` - 工资
- `status` - 状态
- `report_time` - 报工时间
- `createtime` - 创建时间
- `updatetime` - 更新时间

### 3. 如果缺少字段，手动添加
```sql
-- 添加缺失的字段
ALTER TABLE fa_scanwork_report ADD COLUMN employee_id int(11) NOT NULL COMMENT '员工ID' AFTER allocation_id;
ALTER TABLE fa_scanwork_report ADD COLUMN model_id int(11) NOT NULL COMMENT '型号ID' AFTER employee_id;
ALTER TABLE fa_scanwork_report ADD COLUMN process_id int(11) NOT NULL COMMENT '工序ID' AFTER model_id;
ALTER TABLE fa_scanwork_report ADD COLUMN price decimal(10,2) NOT NULL COMMENT '工价（元/件）' AFTER quantity;
ALTER TABLE fa_scanwork_report ADD COLUMN report_time int(11) NOT NULL COMMENT '报工时间' AFTER status;
```

### 4. 添加索引
```sql
-- 添加必要的索引
ALTER TABLE fa_scanwork_report ADD INDEX idx_employee_id (employee_id);
ALTER TABLE fa_scanwork_report ADD INDEX idx_model_id (model_id);
ALTER TABLE fa_scanwork_report ADD INDEX idx_process_id (process_id);
ALTER TABLE fa_scanwork_report ADD INDEX idx_report_time (report_time);
```

### 5. 测试修复结果
访问测试页面验证修复是否成功：
```
http://your-domain/worker/test
```

## 已修复的代码问题

### 1. 控制器查询修复
- 修复了 `application/index/controller/Worker.php` 中的查询语句
- 将 `r.createtime` 改为 `r.report_time`
- 修复了表连接条件

### 2. 数据插入修复
- 确保插入报工记录时包含所有必要字段
- 修复了扫码报工的数据插入

### 3. 前端显示修复
- 修复了前端页面中的时间显示
- 使用正确的字段名显示数据

## 验证步骤

1. **执行修复脚本**：运行 `database/fix_scanwork_tables.sql`
2. **访问测试页面**：`/worker/test`
3. **检查员工首页**：`/worker`
4. **测试报工功能**：尝试提交报工记录

## 如果问题仍然存在

1. 检查数据库连接配置
2. 确认表前缀是否正确（默认是 `fa_`）
3. 检查用户权限是否正确
4. 查看错误日志获取更多信息

## 联系支持

如果按照以上步骤仍然无法解决问题，请提供：
1. 完整的错误信息
2. 数据库表结构（DESCRIBE 结果）
3. 当前使用的FastAdmin版本 