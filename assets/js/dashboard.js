/* 仪表盘主JS，负责页面交互、内容切换、表单拦截、动态加载等 */

// 页面初始化
document.addEventListener('DOMContentLoaded', function() {
    // 导航栏点击切换内容区
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            navLinks.forEach(l => l.parentElement.classList.remove('active'));
            this.parentElement.classList.add('active');
            const contentId = this.getAttribute('data-content') + '-content';
            const contentSection = document.getElementById(contentId);
            if (contentSection) {
                contentSection.classList.add('active');
            } else {
                loadContent(this.getAttribute('data-content'));
            }
        });
    });

    // 管理员头像下拉菜单
    const adminProfile = document.querySelector('.admin-profile');
    if (adminProfile) {
        adminProfile.addEventListener('click', function() {
            this.querySelector('.profile-dropdown').classList.toggle('active');
        });
    }
    // 点击页面其他区域关闭下拉菜单
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.admin-profile')) {
            const dropdowns = document.querySelectorAll('.profile-dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });

    loadContent('dashboard'); // 默认加载控制面板内容
});


// 动态加载内容区
function loadContent(contentType) {
    const contentArea = document.querySelector('.admin-content');
    contentArea.innerHTML = `
        <section id="${contentType}-content" class="content-section active">
            <div class="content-header">
                <h2><i class="fas fa-spinner fa-spin"></i> 正在加载 ${contentType.replace('_', ' ')}...</h2>
            </div>
            <div class="content-body">
                <div class="loader">
                    <div class="spinner"></div>
                    <p>请稍候，内容正在加载中</p>
                </div>
            </div>
        </section>
    `;
    setTimeout(() => {
        fetch('./contents/content_' + contentType + '.php')
            .then(response => {
                if (!response.ok) throw new Error('加载失败');
                return response.text();
            })
            .then(html => {
                contentArea.innerHTML = html;
                /* 绑定事件的目的是，防止提交表单后导致整体页面刷新而丢失表单内容，
                   加载后绑定事件可以保证监听表单提交及其他事件来做出响应， 
                   同时也应当保证进入哪一个选区就绑定哪一个相关事件函数，防止混乱 */ 
                if(contentType == "autoassign") bindAutoAssignEvents();  // 绑定自动分配相关事件
                if(contentType == "dormtransfer") bindDormTransferEvents();  // 绑定学生宿舍调换相关事件
                if(contentType == "dormmanage") bindDormManageEvents(); // 绑定学生宿舍管理相关事件
                if(contentType == "stumanage") bindStuManageEvents();  // 绑定学生管理相关事件
                window.scrollTo({ top: 0, behavior: 'smooth' });  // 滚动到顶部
            })
            .catch(() => {
                contentArea.innerHTML = `
                    <section id="${contentType}-content" class="content-section active">
                        <div class="content-header">
                            <h2>加载失败</h2>
                        </div>
                        <div class="content-body">
                            <p>内容加载失败，请稍后重试。</p>
                        </div>
                    </section>
                `;
            });
    }, 300);
}

// 自动分配相关事件绑定
function bindAutoAssignEvents() {
    const collegeSelect = document.getElementById('college_id');
    if (collegeSelect) {
        collegeSelect.addEventListener('change', function() {
            const form = this.form;
            const formData = new FormData(form);
            console.log('自动分配表单提交数据:', Array.from(formData.entries()));  // 调试输出表单数据
            fetch('./contents/content_autoassign.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const contentArea = document.querySelector('.admin-content');
                contentArea.innerHTML = html;
                bindAutoAssignEvents();

                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            });
        });
    }
    document.querySelectorAll('.auto-assign-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            let submitBtnName = '';
            if (e.submitter) {
                submitBtnName = e.submitter.name;
            }
            const formData = new FormData(form);
            console.log('自动分配表单提交数据:', Array.from(formData.entries()));
            if (submitBtnName) {
                formData.append(submitBtnName, '1');
            }
            fetch('./contents/content_autoassign.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const contentArea = document.querySelector('.admin-content');
                contentArea.innerHTML = html;
                bindAutoAssignEvents();
            });
        });
    });
}

// 宿舍调换/分配相关事件绑定
function bindDormTransferEvents() {
    // 表格分页
    document.querySelectorAll('.pagination .page-link').forEach(function(link){
    link.onclick = function(e){
        e.preventDefault();
        var page = this.getAttribute('data-page');
        if(!page || this.parentNode.classList.contains('disabled') || this.parentNode.classList.contains('active')) return;
        // 保留搜索参数
        var params = new URLSearchParams(window.location.search);
        params.set('page', page);
        params.set('content', 'transfer');
        fetch('./contents/content_dormtransfer.php?' + params.toString())
            .then(res=>res.text())
            .then(html=>{
                document.querySelector('.admin-content').innerHTML = html;
                // 重新绑定事件
                bindDormTransferEvents(); // 如有自定义事件绑定
            });
        }
    });
    // 调换/分配按钮点击，弹出panel
    document.querySelectorAll('.transfer-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var studentId = this.getAttribute('data-student-id');
            var studentName = this.getAttribute('data-student-name');
            var studentGender = this.getAttribute('data-student-gender');
            var studentCollegeId = this.getAttribute('data-student-college-id');
            var studentCollegeName = this.getAttribute('data-student-college-name');
            document.getElementById('modalStudentId').value = studentId;
            document.getElementById('studentInfo').textContent =
                studentName + ' (学号: ' + studentId + ')，学院：' + (studentCollegeName || '');
            document.getElementById('targetRoomId').value = '';
            document.getElementById('reason').value = '';
            document.getElementById('modalStudentGender').value = studentGender;
            document.getElementById('modalStudentCollegeId').value = studentCollegeId;
            document.querySelectorAll('.room-selected').forEach(function(el) {
                el.classList.remove('room-selected');
            });
            // 性别区切换
            var maleSection = document.getElementById('maleDormSection');
            var femaleSection = document.getElementById('femaleDormSection');
            if (studentGender === 'M') {
                if (maleSection) maleSection.style.display = '';
                if (femaleSection) femaleSection.style.display = 'none';
            } else {
                if (maleSection) maleSection.style.display = 'none';
                if (femaleSection) femaleSection.style.display = '';
            }
            // 只显示与当前学生学院绑定的宿舍楼
            document.querySelectorAll('.building-item-flex-vertical').forEach(function(item) {
                var collegeIds = item.getAttribute('data-college-ids');
                if (!studentCollegeId) {
                    item.style.display = 'none';
                } else {
                    var arr = collegeIds.split(',');
                    item.style.display = arr.includes(studentCollegeId) ? '' : 'none';
                }
            });
            var panel = document.getElementById('transferPanel');
            if (panel) panel.style.display = 'block';
            panel.scrollIntoView({behavior: "smooth", block: "center"});
        });
    });

    // 关闭panel
    var closeBtn = document.getElementById('closeTransferPanel');
    var cancelBtn = document.getElementById('cancelTransferPanel');
    function hidePanel() {
        var panel = document.getElementById('transferPanel');
        if (panel) panel.style.display = 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });  // 滚动到顶部
    }
    if (closeBtn) closeBtn.onclick = hidePanel;
    if (cancelBtn) cancelBtn.onclick = hidePanel;

    // 宿舍楼点击加载房间
    document.querySelectorAll('.building-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var buildingId = this.getAttribute('data-building-id');
            var roomList = document.getElementById('rooms-' + buildingId);
            if (!roomList) return;
            var parentSection = this.closest('.gender-section');
            if (parentSection) {
                parentSection.querySelectorAll('.room-list-horizontal').forEach(function(list) {
                    if (list !== roomList) list.style.display = 'none';
                });
            }
            if (roomList.style.display === 'block') {
                roomList.style.display = 'none';
            } else if (roomList.children.length > 0) {
                roomList.style.display = 'block';
            } else {
                roomList.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>';
                roomList.style.display = 'block';
                fetch('../assets/ajax/get_rooms.php?building_id=' + buildingId)   // AJAX请求获取房间列表
                    .then(response => response.text())
                    .then(html => {
                        roomList.innerHTML = html;
                        bindDormTransferEvents();
                    })
                    .catch(() => {
                        roomList.innerHTML = '<div class="alert alert-danger">加载房间列表失败</div>';
                    });
            }
        });
    });

    // 房间选择
    document.querySelectorAll('.room-item').forEach(function(item) {
        item.addEventListener('click', function() {
            document.querySelectorAll('.room-item').forEach(function(el) {
                el.classList.remove('room-selected');  // 先清空已选择状态，保证同时只有一个被选中
            });
            this.classList.add('room-selected');  // 添加当前状态
            document.getElementById('targetRoomId').value = this.getAttribute('data-room-id');
        });
    });

    // 提交调换/分配表单
    var transferForm = document.getElementById('transferForm');
    if (transferForm) {
        transferForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(transferForm);
            formData.append('transfer', '1');
            console.log('提交的表单数据:', Array.from(formData.entries()));  // 调试输出表单数据
            fetch('./contents/content_dormtransfer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())  // 返回请求
            .then(html => {   // 相应内容为html，插入页面上的.admin-content留空区域
                var contentArea = document.querySelector('.admin-content');
                contentArea.innerHTML = html;
                bindDormTransferEvents(); // 重新为新生成的 DOM 元素绑定相关事件，确保交互功能正常
                window.scrollTo({ top: 0, behavior: 'smooth' });  // 滚动到顶部
            });
        });
    }
    // 搜索表单
    var searchForm = document.getElementById('dormTransferSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(searchForm);
        console.log('transfer 搜索表单提交数据:', Array.from(formData.entries()));  // 调试输出表单数据
        var params = new URLSearchParams(formData).toString();
        // 保证学院参数也被包含在URL参数中
        var newUrl = window.location.pathname + '?' + params;
        window.history.pushState({}, '', newUrl);
        fetch('./contents/content_dormtransfer.php?' + params)
            .then(response => response.text())
            .then(html => {
                var contentArea = document.querySelector('.admin-content');
                contentArea.innerHTML = html;
                bindDormTransferEvents();
            });
        });
    }
}

// 宿舍管理相关事件绑定
function bindDormManageEvents() {
    // 搜索表单
    var searchForm = document.getElementById('dormManageSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(searchForm);
            console.log('manage 搜索表单提交数据:', Array.from(formData.entries()));
            var params = new URLSearchParams(formData).toString();
            fetch('./contents/content_dormmanage.php?' + params)
                .then(response => response.text())
                .then(html => {
                    var contentArea = document.querySelector('.admin-content');
                    contentArea.innerHTML = html;
                    bindDormManageEvents();
                });
        });
    }
    // 分页
    document.querySelectorAll('.pagination .page-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var page = this.getAttribute('data-page');
            if (!page) return;
            var url = new URL(window.location.href);
            var params = new URLSearchParams(url.search);
            params.set('page', page);
            params.set('content', 'dormmanage');
            // 保留其他筛选参数
            document.querySelectorAll('#dormManageSearchForm [name]').forEach(function(input) {
                if (input.type === 'checkbox' && !input.checked) return;
                params.set(input.name, input.value);
            });
            fetch('./contents/content_dormmanage.php?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    var contentArea = document.querySelector('.admin-content');
                    contentArea.innerHTML = html;
                    bindDormManageEvents();
                });
        });
    });

    // 属性按钮事件
    document.querySelectorAll('.dorm-attr-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var roomId = this.getAttribute('data-room-id');
            fetch('../assets/AJAX/ajax_dorm_type.php?room_id=' + roomId)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('attrRoomId').value = data.info.room_id;
                    document.getElementById('attrBuildingName').value = data.info.building_name;
                    document.getElementById('attrRoomNumber').value = data.info.room_number;
                    document.getElementById('attrDormType').value = data.info.dorm_type;
                    // 填充成员表
                    var tbody = document.getElementById('attrMemberTable').querySelector('tbody');
                    tbody.innerHTML = '';
                    if (data.members.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">暂无成员</td></tr>';
                    } else {
                        data.members.forEach(function(m) {
                            tbody.innerHTML += `<tr>
                                <td>${m.bed}</td>
                                <td>${m.full_name}</td>
                                <td>${m.gender === 'M' ? '男' : '女'}</td>
                                <td>${m.grade}</td>
                                <td>${m.college_name || ''}</td>
                            </tr>`;
                        });
                    }
                    document.getElementById('dormAttrPanel').style.display = 'block';
                });
        });
    });

    // 关闭panel
    document.getElementById('closeDormAttrPanel').onclick =
    document.getElementById('cancelDormAttrPanel').onclick = function() {
        document.getElementById('dormAttrPanel').style.display = 'none';
    };

    // 保存宿舍类型
    document.getElementById('dormAttrForm').onsubmit = function(e) {
        e.preventDefault();
        var formData = new FormData(this);
         
        fetch('../assets/AJAX/ajax_dorm_type.php', {
            method: 'POST',
            body: formData
        }).then(res => res.text())
        .then(ret => {
            if (ret === 'success') {
                document.getElementById('dormAttrPanel').style.display = 'none';
                // 刷新内容区
                var params = new URLSearchParams(new FormData(document.getElementById('dormManageSearchForm'))).toString();
                alert('保存成功，宿舍类型已修改！');
                var contentArea = document.querySelector('.admin-content');
                contentArea.innerHTML = `
                <section id="dormmanage-content" class="content-section active">
                    <div class="content-header">
                        <h2><i class="fas fa-spinner fa-spin"></i> 正在加载 dormmanage...</h2>
                    </div>
                    <div class="content-body">
                        <div class="loader">
                            <div class="spinner"></div>
                            <p>请稍候，内容正在加载中</p>
                        </div>
                    </div>
                </section>
                `;
                setTimeout(() => {
                    fetch('./contents/content_dormmanage.php?' + params)
                        .then(response => response.text())
                        .then(html => {
                            var contentArea = document.querySelector('.admin-content');
                            contentArea.innerHTML = html;
                            bindDormManageEvents();
                        });
                }, 300);
            } else {
                alert('保存失败，请重试');
            }
        });
    }
}

// 学生管理相关事件绑定
function bindStuManageEvents() {
    // 筛选表单（查询）
    var searchForm = document.getElementById('stuManageSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(searchForm);
            var params = new URLSearchParams(formData).toString();
            fetch('./contents/content_stumanage.php?' + params)
                .then(response => response.text())
                .then(html => {
                    var contentArea = document.querySelector('.admin-content');
                    contentArea.innerHTML = html;
                    bindStuManageEvents();
                });
        });
    }

    // 添加学生表单
    var addForm = document.getElementById('addStudentForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(addForm);
            var grade = formData.get('grade');
            var name = formData.get('full_name');
            var gender = formData.get('gender') == 'M' ? '男': '女';
            if (!grade || !name || !gender) {
                alert('请完整填写学生信息！');
                return false;
            }else if (!/^\d{4}$/.test(grade)) {
                alert('输入格式错误，年级必须为四位年数');
                return false; 
            }else if (grade < 2000 || grade > new Date().getFullYear()) {
                alert('年级必须在2000年到当前年份之间');
                return false;
            }

            var msg = '您将要添加的学生信息为:\n' +
                '姓名: ' + name + '\n' +
                '性别: ' + gender + '\n' +
                '年级: ' + grade + '\n' + '是否确认?';
            if (!confirm(msg)) return false;

            formData.append('add_student', '1');   // 确认为添加学生表单
            fetch('./contents/content_stumanage.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                var contentArea = document.querySelector('.admin-content');
                contentArea.innerHTML = html;
                bindStuManageEvents();
            });
        });
    }

    // 批量删除表单
    var delForm = document.getElementById('deleteStudentsForm');
    if (delForm) {
        delForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var checkedCount = document.querySelectorAll('input[name="student_ids[]"]:checked').length;
            if (checkedCount === 0) {
                alert('请先选择要删除的学生');
                return false;
            }
            //var msg = '确定要删除所选学生吗？此操作不可恢复！';
            if (checkedCount > 1) {
                msg = '您选择了多个学生，这将删除所有所选学生，确定继续？';
                if (!confirm(msg)) return false;
            }
            var formData = new FormData(delForm);
            formData.append('delete_students', '1');  // 确认为删除操作表单
            fetch('./contents/content_stumanage.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                var contentArea = document.querySelector('.admin-content');
                contentArea.innerHTML = html;
                bindStuManageEvents();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }

    // 全选
    var selectAll = document.getElementById('selectAllStu');
    if (selectAll) {
        selectAll.onclick = function() {
            var checked = this.checked;
            document.querySelectorAll('input[name="student_ids[]"]').forEach(function(cb) {
                cb.checked = checked;
            });
        };
    }

    // 单个删除
    document.querySelectorAll('.single-del-btn').forEach(function(btn) {
        btn.onclick = function() {
            var stuId = this.getAttribute('data-stu-id');
            if (confirm('确定要删除该学生吗？此操作不可恢复！')) {
                var form = document.getElementById('deleteStudentsForm');
                document.querySelectorAll('input[name="student_ids[]"]').forEach(function(cb) {
                    cb.checked = (cb.value === stuId);
                });
                // 触发表单提交（AJAX）
                var event = new Event('submit', { bubbles: true, cancelable: true });
                form.dispatchEvent(event);
            }
        }
    });

    // 分页
    document.querySelectorAll('.pagination .page-link').forEach(function(link) {
        link.onclick = function(e) {
            e.preventDefault();
            var page = this.getAttribute('data-page');
            if (!page) return;
            var url = new URL(window.location.href);
            var params = new URLSearchParams(url.search);
            params.set('page', page);
            params.set('content', 'stumanage');
            // 保留其他筛选参数
            document.querySelectorAll('#stuManageSearchForm [name]').forEach(function(input) {
                if (input.type === 'checkbox' && !input.checked) return;
                params.set(input.name, input.value);
            });
            fetch('./contents/content_stumanage.php?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    var contentArea = document.querySelector('.admin-content');
                    contentArea.innerHTML = html;
                    bindStuManageEvents();
                });
        };
    });
}

// 回到顶部按钮
window.addEventListener('scroll', function() {
    var btn = document.getElementById('backToTopBtn');
    if (!btn) return;
    if (window.scrollY > 200) {
        btn.style.display = 'block';  // 滚动超过200px时显示按钮
    } else {
        btn.style.display = 'none';
    }
});

function slowScrollToTop(duration = 800) {
    const start = window.scrollY;
    const startTime = performance.now();

    function scrollStep(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // easeOutCubic 缓动效果
        const ease = 1 - Math.pow(1 - progress, 3);
        window.scrollTo(0, start * (1 - ease));
        if (progress < 1) {
            requestAnimationFrame(scrollStep);
        }
    }
    requestAnimationFrame(scrollStep);
}

// 点击按钮回到顶部
document.getElementById('backToTopBtn').onclick = function() {
    slowScrollToTop(1200); 
};