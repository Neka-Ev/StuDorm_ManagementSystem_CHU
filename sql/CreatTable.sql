USE DormDB

-- ѧԺ���洢ѧУ��ѧԺ��Ϣ
CREATE TABLE Colleges (
    college_id INT PRIMARY KEY IDENTITY(1,1),          -- ѧԺID����������
    college_name NVARCHAR(100) NOT NULL              -- ѧԺ���ƣ���"�����ѧԺ"
);
GO
-- ����¥����¼����¥������Ϣ
CREATE TABLE DormitoryBuildings (
    building_id INT PRIMARY KEY IDENTITY(1,1),       -- ����¥ID����������
    building_name NVARCHAR(50) NOT NULL,              -- ����¥���ƣ���"1��¥"
    gender CHAR(1) CHECK (gender IN ('M','F')) NOT NULL, -- �Ա����ƣ�M(��)/F(Ů)
    total_floors INT,                                 -- ��¥����
    created_at DATETIME DEFAULT GETDATE(),           -- ����ʱ��
);
GO
-- ����ѧԺ�󶨱�
CREATE TABLE CollegeDormitoryBinding (
    building_id INT NOT NULL,
	college_id INT NOT NULL,  
    PRIMARY KEY (building_id,college_id),
	FOREIGN KEY (college_id) REFERENCES Colleges(college_id), 
    FOREIGN KEY (building_id) REFERENCES DormitoryBuildings(building_id),
)
GO
-- ���᷿�����¼ÿ�����᷿�����ϸ��Ϣ
CREATE TABLE DormitoryRooms (
    room_id INT PRIMARY KEY IDENTITY(1,1),            -- ����ID����������
    building_id INT NOT NULL,                         -- ��������¥ID
    floor INT NOT NULL,                              -- ����¥��
    room_number NVARCHAR(10) NOT NULL,              -- ����ţ���"201"
    capacity INT DEFAULT 4,                          -- ����������Ĭ��4�ˣ�
    current_occupancy INT DEFAULT 0,                 -- ��ǰ��ס����
    created_at DATETIME DEFAULT GETDATE(),           -- ����ʱ��
	dorm_type NVARCHAR(50) DEFAULT '��ͨ����',        --�������ͣ�'��������'��'��ͨ����'��'ʾ������'�ȵȣ�Ĭ��Ϊ��ͨ����
    FOREIGN KEY (building_id) REFERENCES DormitoryBuildings(building_id), -- �����������¥
    CHECK (current_occupancy <= capacity),            -- ���Լ������ס��������������
	CONSTRAINT CHK_DormType CHECK (dorm_type IN ('��ͨ����', '��������','ʾ������')) -- ���Լ��
);
GO
-- ѧ�����洢ѧ��������Ϣ��ס�����
CREATE TABLE Students (
    student_id VARCHAR(10) PRIMARY KEY,        -- ѧ��ID���ɴ���������
    full_name NVARCHAR(100) NOT NULL,               -- ѧ������
    gender CHAR(1) CHECK (gender IN ('M','F')) NOT NULL, -- �Ա�M(��)/F(Ů)
    college_id INT NOT NULL,                        -- ����ѧԺID
    dorm_building_id INT,                           -- ��ǰס��¥ID����ΪNULL��ʾδ���䣩
    dorm_room_id INT,                               -- ��ǰ����ID����ΪNULL��ʾδ���䣩
    admission_date DATE,                            -- ��ѧ����
    created_at DATETIME DEFAULT GETDATE(),          -- ����ʱ��
    FOREIGN KEY (college_id) REFERENCES Colleges(college_id),          -- �������ѧԺ
    FOREIGN KEY (dorm_building_id) REFERENCES DormitoryBuildings(building_id), -- �����������¥
    FOREIGN KEY (dorm_room_id) REFERENCES DormitoryRooms(room_id)       -- �����������
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
-- ס�޷����¼����¼ѧ��ס�޷�����ʷ
CREATE TABLE Allocations (
    allocation_id INT PRIMARY KEY IDENTITY(1,1),     -- �����¼ID����������
    student_id VARCHAR(10) NOT NULL,                        -- ѧ��ID
    room_id INT NOT NULL,                           -- ����ķ���ID
    date_allocated DATETIME DEFAULT GETDATE(),      -- ��������
    created_by INT,                                  -- �����Ĺ���ԱID
    allocation_type NVARCHAR(20) DEFAULT 'initial', -- �������ͣ�initial(��ʼ)/transfer(����)
    is_active BIT DEFAULT 1,                        -- �Ƿ�ǰ��Ч���䣨1=��Ч��
    notes NVARCHAR(500),                            -- ���䱸ע��Ϣ
    FOREIGN KEY (student_id) REFERENCES Students(student_id),          -- �������ѧ��
    FOREIGN KEY (room_id) REFERENCES DormitoryRooms(room_id),           -- �����������
    FOREIGN KEY (created_by) REFERENCES Administrators(admin_id),      -- �����������Ա
    CONSTRAINT CHK_AllocationType CHECK (allocation_type IN ('initial', 'transfer')) -- ���Լ��
);
GO

-- ���������¼����¼ѧ�������������ʷ
CREATE TABLE Transfers (
    transfer_id INT PRIMARY KEY IDENTITY(1,1),       -- ������¼ID����������
    student_id VARCHAR(10) NOT NULL,                        -- ѧ��ID
    from_room_id INT NOT NULL,                      -- ԭ���᷿��ID
    to_room_id INT NOT NULL,                        -- �����᷿��ID
    transfer_date DATETIME DEFAULT GETDATE(),       -- ��������
    processed_by INT,                              -- ��������Ĺ���ԱID
    reason NVARCHAR(500),                           -- ����ԭ��˵��
    approval_status NVARCHAR(20) DEFAULT 'approved',-- ����״̬��approved/pending/rejected
    FOREIGN KEY (student_id) REFERENCES Students(student_id),          -- �������ѧ��
    FOREIGN KEY (from_room_id) REFERENCES DormitoryRooms(room_id),     -- �������ԭ����
    FOREIGN KEY (to_room_id) REFERENCES DormitoryRooms(room_id),       -- ��������·���
    FOREIGN KEY (processed_by) REFERENCES Administrators(admin_id),    -- �����������Ա
    CONSTRAINT CHK_DifferentRooms CHECK (from_room_id <> to_room_id),  -- ���Լ�������ܵ�����ͬһ����
    CONSTRAINT CHK_ApprovalStatus CHECK (approval_status IN ('approved', 'pending', 'rejected')) -- ���Լ��
);
GO

INSERT INTO Administrators (username, password, full_name) 
VALUES 
    ('neka', 'asd123456', '��������Ա'),
	('CICO','P88888888','��������Ա'),
    ('dorm_manager', '654321', '��ͨ����Ա'),
    ('finance_admin', '123345', '��ͨ����Ա');
	GO


-- �����������Զ�����ѧ��ID
CREATE TRIGGER tr_Students_GenerateID
ON Students
INSTEAD OF INSERT
AS
BEGIN
    DECLARE @year CHAR(4);
    DECLARE @max_seq INT;
    
    -- ��ȡ��ѧ���
    SELECT @year = CAST(YEAR(admission_date) AS CHAR(4)) FROM inserted;
    
    -- ��ȡ��ǰ���������
    SELECT @max_seq = ISNULL(MAX(CAST(RIGHT(student_id, 4) AS INT)), 0)
    FROM Students
    WHERE student_id LIKE @year + '%';
    
    -- �������ݲ�����ѧ��ID
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


----����������ȷ��ÿ��ѧ��ֻ��һ����Ծ�����¼
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
        RAISERROR('ÿ��ѧ��ֻ����һ����Ծ�ķ����¼', 16, 1);
        ROLLBACK TRANSACTION;
    END
END;
GO

-- ���ᵱǰ��ס�����ͼ
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

-- Ϊ����������������߲�ѯ����
CREATE INDEX IX_Students_College ON Students(college_id);
CREATE INDEX IX_Students_Dorm ON Students(dorm_building_id, dorm_room_id);
CREATE INDEX IX_DormitoryRooms_Building ON DormitoryRooms(building_id);
CREATE INDEX IX_Allocations_Room ON Allocations(room_id);
CREATE INDEX IX_Transfers_FromRoom ON Transfers(from_room_id);
CREATE INDEX IX_Transfers_ToRoom ON Transfers(to_room_id);