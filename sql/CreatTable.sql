USE DormDB

-- 学院表：存储学校各学院信息
CREATE TABLE Colleges (
    college_id INT PRIMARY KEY IDENTITY(1,1),          -- 学院ID，自增主键
    college_name NVARCHAR(100) NOT NULL              -- 学院名称，如"计算机学院"
);
GO
-- 宿舍楼表：记录宿舍楼基本信息
CREATE TABLE DormitoryBuildings (
    building_id INT PRIMARY KEY IDENTITY(1,1),       -- 宿舍楼ID，自增主键
    building_name NVARCHAR(50) NOT NULL,              -- 宿舍楼名称，如"1号楼"
    gender CHAR(1) CHECK (gender IN ('M','F')) NOT NULL, -- 性别限制：M(男)/F(女)
    total_floors INT,                                 -- 总楼层数
    created_at DATETIME DEFAULT GETDATE(),           -- 创建时间
);
GO
-- 宿舍学院绑定表
CREATE TABLE CollegeDormitoryBinding (
    building_id INT NOT NULL,
	college_id INT NOT NULL,  
    PRIMARY KEY (building_id,college_id),
	FOREIGN KEY (college_id) REFERENCES Colleges(college_id), 
    FOREIGN KEY (building_id) REFERENCES DormitoryBuildings(building_id),
)
GO
-- 宿舍房间表：记录每个宿舍房间的详细信息
CREATE TABLE DormitoryRooms (
    room_id INT PRIMARY KEY IDENTITY(1,1),            -- 房间ID，自增主键
    building_id INT NOT NULL,                         -- 所属宿舍楼ID
    floor INT NOT NULL,                              -- 所在楼层
    room_number NVARCHAR(10) NOT NULL,              -- 房间号，如"201"
    capacity INT DEFAULT 4,                          -- 房间容量（默认4人）
    current_occupancy INT DEFAULT 0,                 -- 当前入住人数
    created_at DATETIME DEFAULT GETDATE(),           -- 创建时间
	dorm_type NVARCHAR(50) DEFAULT '普通宿舍',        --宿舍类型，'文明宿舍'，'普通宿舍'，'示范宿舍'等等，默认为普通宿舍
    FOREIGN KEY (building_id) REFERENCES DormitoryBuildings(building_id), -- 外键关联宿舍楼
    CHECK (current_occupancy <= capacity),            -- 检查约束：入住人数不超过容量
	CONSTRAINT CHK_DormType CHECK (dorm_type IN ('普通宿舍', '文明宿舍','示范宿舍')) -- 检查约束
);
GO
-- 学生表：存储学生基本信息及住宿情况
CREATE TABLE Students (
    student_id VARCHAR(10) PRIMARY KEY,        -- 学生ID，由触发器生成
    full_name NVARCHAR(100) NOT NULL,               -- 学生姓名
    gender CHAR(1) CHECK (gender IN ('M','F')) NOT NULL, -- 性别：M(男)/F(女)
    college_id INT NOT NULL,                        -- 所属学院ID
    dorm_building_id INT,                           -- 当前住宿楼ID（可为NULL表示未分配）
    dorm_room_id INT,                               -- 当前房间ID（可为NULL表示未分配）
    admission_date DATE,                            -- 入学日期
    created_at DATETIME DEFAULT GETDATE(),          -- 创建时间
    FOREIGN KEY (college_id) REFERENCES Colleges(college_id),          -- 外键关联学院
    FOREIGN KEY (dorm_building_id) REFERENCES DormitoryBuildings(building_id), -- 外键关联宿舍楼
    FOREIGN KEY (dorm_room_id) REFERENCES DormitoryRooms(room_id)       -- 外键关联房间
);
GO

CREATE TABLE Administrators (
    admin_id INT PRIMARY KEY IDENTITY(1,1),
    username NVARCHAR(50) UNIQUE NOT NULL,
    password NVARCHAR(255) NOT NULL,
    full_name NVARCHAR(50),
    last_login DATETIME,
    created_at DATETIME DEFAULT GETDATE()
);
GO
-- 住宿分配记录表：记录学生住宿分配历史
CREATE TABLE Allocations (
    allocation_id INT PRIMARY KEY IDENTITY(1,1),     -- 分配记录ID，自增主键
    student_id VARCHAR(10) NOT NULL,                        -- 学生ID
    room_id INT NOT NULL,                           -- 分配的房间ID
    date_allocated DATETIME DEFAULT GETDATE(),      -- 分配日期
    created_by INT,                                  -- 操作的管理员ID
    allocation_type NVARCHAR(20) DEFAULT 'initial', -- 分配类型：initial(初始)/transfer(调换)
    is_active BIT DEFAULT 1,                        -- 是否当前有效分配（1=有效）
    notes NVARCHAR(500),                            -- 分配备注信息
    FOREIGN KEY (student_id) REFERENCES Students(student_id),          -- 外键关联学生
    FOREIGN KEY (room_id) REFERENCES DormitoryRooms(room_id),           -- 外键关联房间
    FOREIGN KEY (created_by) REFERENCES Administrators(admin_id),      -- 外键关联管理员
    CONSTRAINT CHK_AllocationType CHECK (allocation_type IN ('initial', 'transfer')) -- 检查约束
);
GO

-- 宿舍调换记录表：记录学生调换宿舍的历史
CREATE TABLE Transfers (
    transfer_id INT PRIMARY KEY IDENTITY(1,1),       -- 调换记录ID，自增主键
    student_id VARCHAR(10) NOT NULL,                        -- 学生ID
    from_room_id INT NOT NULL,                      -- 原宿舍房间ID
    to_room_id INT NOT NULL,                        -- 新宿舍房间ID
    transfer_date DATETIME DEFAULT GETDATE(),       -- 调换日期
    processed_by INT,                              -- 处理调换的管理员ID
    reason NVARCHAR(500),                           -- 调换原因说明
    approval_status NVARCHAR(20) DEFAULT 'approved',-- 审批状态：approved/pending/rejected
    FOREIGN KEY (student_id) REFERENCES Students(student_id),          -- 外键关联学生
    FOREIGN KEY (from_room_id) REFERENCES DormitoryRooms(room_id),     -- 外键关联原房间
    FOREIGN KEY (to_room_id) REFERENCES DormitoryRooms(room_id),       -- 外键关联新房间
    FOREIGN KEY (processed_by) REFERENCES Administrators(admin_id),    -- 外键关联管理员
    CONSTRAINT CHK_DifferentRooms CHECK (from_room_id <> to_room_id),  -- 检查约束：不能调换到同一房间
    CONSTRAINT CHK_ApprovalStatus CHECK (approval_status IN ('approved', 'pending', 'rejected')) -- 检查约束
);
GO

INSERT INTO Administrators (username, password, full_name) 
VALUES 
    ('neka', 'asd123456', '超级管理员'),
	('CICO','P88888888','超级管理员'),
    ('dorm_manager', '654321', '普通管理员'),
    ('finance_admin', '123345', '普通管理员');
	GO


-- 创建触发器自动生成学生ID
CREATE TRIGGER tr_Students_GenerateID
ON Students
INSTEAD OF INSERT
AS
BEGIN
    DECLARE @year CHAR(4);
    DECLARE @max_seq INT;
    
    -- 获取入学年份
    SELECT @year = CAST(YEAR(admission_date) AS CHAR(4)) FROM inserted;
    
    -- 获取当前年份最大序号
    SELECT @max_seq = ISNULL(MAX(CAST(RIGHT(student_id, 4) AS INT)), 0)
    FROM Students
    WHERE student_id LIKE @year + '%';
    
    -- 插入数据并生成学生ID
    INSERT INTO Students (student_id, full_name, gender, college_id, admission_date, created_at)
    SELECT 
        @year + RIGHT('0000' + CAST((@max_seq + ROW_NUMBER() OVER (ORDER BY (SELECT NULL))) AS VARCHAR(4)), 4),
        full_name, 
        gender, 
        college_id, 
        admission_date,
        GETDATE()
    FROM inserted;
END;
GO


----创建触发器确保每个学生只有一个活跃分配记录
CREATE TRIGGER tr_Allocations_CheckSingleActive
ON Allocations
AFTER INSERT, UPDATE
AS
BEGIN
    IF EXISTS (
        SELECT 1 
        FROM Allocations 
        WHERE is_active = 1
        GROUP BY student_id 
        HAVING COUNT(*) > 1
    )
    BEGIN
        RAISERROR('每个学生只能有一个活跃的分配记录', 16, 1);
        ROLLBACK TRANSACTION;
    END
END;
GO

-- 宿舍当前入住情况视图
CREATE VIEW DormRoomOccupancyPivotView AS
WITH NumberedOccupants AS (
    SELECT 
        r.room_id,
        b.building_name,
        r.room_number,
		r.dorm_type,
        s.full_name,
        ROW_NUMBER() OVER (PARTITION BY r.room_id ORDER BY s.student_id) AS bed_position
    FROM DormitoryRooms r
    JOIN DormitoryBuildings b ON r.building_id = b.building_id
    LEFT JOIN Students s ON r.room_id = s.dorm_room_id
)
SELECT 
    room_id,
    building_name,
    room_number,
	dorm_type,
    MAX(CASE WHEN bed_position = 1 THEN full_name END) AS occupant1,
    MAX(CASE WHEN bed_position = 2 THEN full_name END) AS occupant2,
    MAX(CASE WHEN bed_position = 3 THEN full_name END) AS occupant3,
    MAX(CASE WHEN bed_position = 4 THEN full_name END) AS occupant4,
    COUNT(full_name) AS actual_count
FROM NumberedOccupants
GROUP BY room_id, building_name, room_number,dorm_type;
GO

-- 为各表创建常规索引提高查询性能
CREATE INDEX IX_Students_College ON Students(college_id);
CREATE INDEX IX_Students_Dorm ON Students(dorm_building_id, dorm_room_id);
CREATE INDEX IX_DormitoryRooms_Building ON DormitoryRooms(building_id);
CREATE INDEX IX_Allocations_Room ON Allocations(room_id);
CREATE INDEX IX_Transfers_FromRoom ON Transfers(from_room_id);
CREATE INDEX IX_Transfers_ToRoom ON Transfers(to_room_id);