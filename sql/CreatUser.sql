---- 1. ������¼��
IF NOT EXISTS (SELECT name FROM master.sys.server_principals WHERE name = 'dormdb_user')
BEGIN
    CREATE LOGIN dormdb_user 
    WITH PASSWORD = 'P88888888', 
    CHECK_POLICY = ON,
    CHECK_EXPIRATION = ON;
    
    PRINT '��¼�� dormdb_user �����ɹ�';
END
ELSE
BEGIN
    PRINT '��¼�� dormdb_user �Ѵ���';
END
GO

---- 2. �����û���ӳ�䵽��¼��
USE DormDB;
GO

IF NOT EXISTS (SELECT name FROM sys.database_principals WHERE name = 'dorm_user')
BEGIN
    CREATE USER dorm_user FOR LOGIN dormdb_user;
    PRINT '�û��� dorm_user �����ɹ�';
END
ELSE
BEGIN
    PRINT '�û��� dorm_user �Ѵ���';
END
GO

USE DormDB

---- 3. ������ͨ�����¼��
IF NOT EXISTS (SELECT name FROM master.sys.server_principals WHERE name = 'dormdb_admin')
BEGIN
    CREATE LOGIN dormdb_user 
    WITH PASSWORD = 'AAA666666', 
    CHECK_POLICY = ON,
    CHECK_EXPIRATION = ON;
    
    PRINT '��¼�� dormdb_admin �����ɹ�';
END
ELSE
BEGIN
    PRINT '��¼�� dormdb_admin �Ѵ���';
END
GO

---- 4. �����û���ӳ�䵽��ͨ�����¼��
USE DormDB;
GO

IF NOT EXISTS (SELECT name FROM sys.database_principals WHERE name = 'dorm_admin')
BEGIN
    CREATE USER dorm_user FOR LOGIN dormdb_admin;
    PRINT '�û��� dorm_admin �����ɹ�';
END
ELSE
BEGIN
    PRINT '�û��� dorm_admin �Ѵ���';
END
GO

--��Ȩ��������Ա��ӵ�����б�����ͼ������Ȩ��

GRANT ALL PRIVILEGES ON Colleges TO dormdb_user
GRANT ALL PRIVILEGES ON Allocations TO dormdb_user
GRANT ALL PRIVILEGES ON CollegeDormitoryBinding TO dormdb_user
GRANT ALL PRIVILEGES ON DormitoryBuildings TO dormdb_user
GRANT ALL PRIVILEGES ON Students TO dormdb_user
GRANT ALL PRIVILEGES ON DormitoryRooms TO dormdb_user
GRANT ALL PRIVILEGES ON Administrators TO dormdb_user
GRANT ALL PRIVILEGES ON Transfers TO dormdb_user
GRANT ALL PRIVILEGES ON DormRoomOccupancyPivotView TO dormdb_user

--��Ȩ��ͨ����Ա��������ɾ��Ȩ��

GRANT ALL PRIVILEGES ON Colleges TO dormdb_admin
GRANT ALL PRIVILEGES ON Allocations TO dormdb_admin
GRANT ALL PRIVILEGES ON CollegeDormitoryBinding TO dormdb_admin
GRANT ALL PRIVILEGES ON DormitoryBuildings TO dormdb_admin
GRANT SELECT, UPDATE ON Students TO dormdb_admin
GRANT ALL PRIVILEGES ON DormitoryRooms TO dormdb_admin
GRANT ALL PRIVILEGES ON Administrators TO dormdb_admin
GRANT ALL PRIVILEGES ON Transfers TO dormdb_admin
GRANT ALL PRIVILEGES ON DormRoomOccupancyPivotView TO dormdb_admin