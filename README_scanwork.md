# 工厂报工管理系统

基于 FastAdmin 框架开发的工厂生产报工管理系统，实现从订单录入到员工报工的全流程数字化管理。

## 系统架构

- **后端框架**: FastAdmin (基于 ThinkPHP 5.1)
- **前端框架**: Bootstrap + jQuery
- **数据库**: MySQL
- **表前缀**: `fa_scanwork_`

## 功能模块

### 第一阶段：基础数据模块

#### 1. 产品与型号管理
- **产品管理**: 新增/编辑/删除产品，支持产品名称、规格管理
- **型号管理**: 为产品添加具体型号，支持型号描述
- **关联关系**: 产品与型号为 1对多 关系

#### 2. 工序与工价管理
- **工序管理**: 新增/编辑/删除工序，支持工序描述
- **工价设置**: 为"型号+工序"绑定工价，支持批量设置
- **唯一约束**: 同一型号的同一工序只能设置一个工价

### 第二阶段：订单与分工模块

#### 3. 订单管理
- **订单录入**: 录入订单号、客户信息，选择产品型号并填写数量
- **订单列表**: 展示所有订单，包含订单状态跟踪
- **订单详情**: 查看订单完整信息和分工进度
- **状态管理**: 订单状态自动更新（待生产→生产中→已完成）

#### 4. 分工分配
- **任务分配**: 选择订单→型号→工序→员工，分配生产任务
- **分配列表**: 展示所有分配记录，包含进度跟踪
- **批量分配**: 支持批量分配多个型号的多个工序
- **数据校验**: 确保分配的型号属于订单，工序有对应工价

### 第三阶段：报工与进度模块

#### 5. 报工管理
- **员工端报工**: 员工查看自己的任务，提交报工记录
- **管理端确认**: 管理员确认报工记录，自动计算工资
- **扫码报工**: 通过扫描二维码快速报工
- **报工验证**: 验证报工数量不超过分配数量

#### 6. 生产进度可视化
- **实时看板**: 显示今日报工数量、工资总额等关键指标
- **进度图表**: 使用ECharts展示订单进度、员工工作量等统计图表
- **趋势分析**: 日报工趋势、工序效率等数据分析
- **多维度统计**: 订单状态、员工工作量、工序效率等多维度统计

#### 7. 计件工资自动计算
- **工资计算**: 根据报工数量和工序工价自动计算工资
- **工资统计**: 按员工、时间等维度统计工资
- **工资明细**: 查看详细的工资计算明细
- **实时更新**: 报工确认后实时更新相关数据

## 安装部署

### 1. 数据库配置
执行 `scanwork_tables.sql` 文件创建数据表：

```sql
-- 导入数据库表结构
mysql -u username -p database_name < scanwork_tables.sql
```

### 2. 文件部署
将以下目录复制到 FastAdmin 项目中：

```
application/admin/controller/scanwork/     # 控制器
application/admin/model/scanwork/          # 模型
application/admin/view/scanwork/           # 视图
```

### 3. 权限配置
在 FastAdmin 后台添加菜单和权限：

- 产品管理: `scanwork/product`
- 型号管理: `scanwork/model`
- 工序管理: `scanwork/process`
- 工序工价: `scanwork/processprice`
- 订单管理: `scanwork/order`
- 分工分配: `scanwork/allocation`
- 报工管理: `scanwork/report`
- 生产进度: `scanwork/progress`
- 员工端报工: `index/worker/*` (使用FastAdmin用户中心)
- 二维码管理: `scanwork/qrcode`

## 使用说明

### 产品管理
1. 进入"产品管理"页面
2. 点击"添加"按钮新增产品
3. 填写产品名称和规格信息
4. 保存后可在型号管理中为该产品添加型号

### 型号管理
1. 进入"型号管理"页面
2. 点击"添加"按钮新增型号
3. 选择所属产品，填写型号名称和描述
4. 保存后可在工序工价中设置该型号的工序工价

### 工序管理
1. 进入"工序管理"页面
2. 点击"添加"按钮新增工序
3. 填写工序名称和描述信息
4. 保存后可在工序工价中设置该工序的工价

### 工序工价管理
1. 进入"工序工价管理"页面
2. 可单独添加或使用"批量设置工价"功能
3. 批量设置：选择型号，为所有工序设置工价
4. 系统会自动检查重复设置并提示

### 订单管理
1. 进入"订单管理"页面
2. 点击"添加"按钮新增订单
3. 填写客户信息，选择型号并设置数量
4. 保存后可在分工分配中为该订单分配任务

### 分工分配
1. 进入"分工分配"页面
2. 可单独添加或使用"批量分配"功能
3. 选择订单→型号→工序→员工，设置分配数量
4. 系统会自动验证数据完整性和数量限制

### 报工管理
1. 进入"报工管理"页面查看所有报工记录
2. 管理员可确认报工记录，系统自动计算工资

### 员工端报工
1. 员工访问前台地址：`http://域名/index/worker/index`
2. 使用FastAdmin用户中心账号登录系统
3. 在"我的任务"页面查看分配的任务
4. 点击"报工"按钮提交报工记录
5. 支持扫码报工和手动报工两种方式
6. 可查看个人报工记录和工资统计

### 生产进度可视化
1. 进入"生产进度"页面查看实时数据
2. 包含订单状态、员工工作量、工序效率等图表
3. 支持按时间范围查询日报工趋势
4. 提供详细的订单进度表格

### 工资统计
1. 进入"报工管理"→"工资统计"页面
2. 选择时间范围和员工进行查询
3. 查看员工工资汇总和详细明细
4. 支持导出工资统计数据

### 二维码管理
1. 进入"二维码管理"页面查看所有任务
2. 选择任务生成对应的二维码
3. 支持批量生成多个任务的二维码
4. 可下载二维码图片或打印二维码标签
5. 二维码包含完整的任务信息，便于扫码报工

### 扫码报工
1. 员工在前台"扫码报工"页面
2. 支持摄像头扫码和手动输入两种方式
3. 扫描二维码后自动识别任务信息
4. 输入报工数量后提交报工记录
5. 支持移动端摄像头扫码功能

## 技术特点

1. **模块化设计**: 按业务功能模块化开发，便于维护
2. **数据完整性**: 通过外键约束和业务逻辑确保数据一致性
3. **用户友好**: 直观的界面设计和操作流程
4. **扩展性强**: 基于 FastAdmin 框架，便于功能扩展
5. **移动端适配**: 响应式设计，完美支持手机和平板
6. **二维码集成**: 支持二维码生成、扫码报工功能
7. **前端优化**: 独立的JS文件，代码复用和维护性更好

## 开发计划

- [x] 第一阶段：基础数据模块
- [x] 第二阶段：订单与分工模块
- [x] 第三阶段：报工与进度模块
- [x] 第四阶段：二维码扫码功能
- [x] 第五阶段：移动端适配

、前端菜单建议
1. 工人端页面菜单（举例）
你可以在工人端导航栏或首页添加如下入口：
我的计时分工任务：/index/worker/ttasks
计时报工：/index/worker/treport
我的计时报工记录：/index/worker/trecords
我的计时工资统计：/index/worker/twage
<ul class="nav nav-pills">
  <li><a href="/index/worker/ttasks">计时分工任务</a></li>
  <li><a href="/index/worker/treport">计时报工</a></li>
  <li><a href="/index/worker/trecords">计时报工记录</a></li>
  <li><a href="/index/worker/twage">计时工资统计</a></li>
</ul>

## 联系方式

如有问题或建议，请联系开发团队。 