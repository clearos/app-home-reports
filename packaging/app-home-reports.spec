
Name: app-home-reports
Epoch: 1
Version: 2.3.0
Release: 1%{dist}
Summary: Home Reports
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Provides: system-report-driver
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base
Requires: app-reports >= 1:1.4.2

%description
The Home Reports driver is designed for home environments.

%package core
Summary: Home Reports - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-reports-core >= 1:1.4.2

%description core
The Home Reports driver is designed for home environments.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/home_reports
cp -r * %{buildroot}/usr/clearos/apps/home_reports/


%post
logger -p local6.notice -t installer 'app-home-reports - installing'

%post core
logger -p local6.notice -t installer 'app-home-reports-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/home_reports/deploy/install ] && /usr/clearos/apps/home_reports/deploy/install
fi

[ -x /usr/clearos/apps/home_reports/deploy/upgrade ] && /usr/clearos/apps/home_reports/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-home-reports - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-home-reports-core - uninstalling'
    [ -x /usr/clearos/apps/home_reports/deploy/uninstall ] && /usr/clearos/apps/home_reports/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/home_reports/controllers
/usr/clearos/apps/home_reports/htdocs
/usr/clearos/apps/home_reports/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/home_reports/packaging
%dir /usr/clearos/apps/home_reports
/usr/clearos/apps/home_reports/deploy
/usr/clearos/apps/home_reports/language
/usr/clearos/apps/home_reports/libraries
