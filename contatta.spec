Name: contatta
Version: 0.0.3
Release: 1%{?dist}
Summary: Rest API for FreePBX
Group: Network
License: GPLv2
Source0: %{name}-%{version}.tar.gz
Source1: contatta.tar.gz
BuildRequires: nethserver-devtools, gettext
Buildarch: x86_64
Requires: nethserver-freepbx, nethserver-samba

%description
Rest API for FreePBX

%prep
%setup


%build
perl createlinks
for PO in $(find ./ -name "*\.po" | grep 'i18n\/[a-z][a-z]_[A-Z][A-Z]')
    do msgfmt -o $(echo ${PO} | sed 's/\.po$/.mo/g') ${PO}
done

%install
rm -rf %{buildroot}
(cd root; find . -depth -print | cpio -dump %{buildroot})
mkdir -p %{buildroot}/usr/src/nethvoice/modules
mv %{S:1} %{buildroot}/usr/src/contatta/modules/

%{genfilelist} %{buildroot} \
> %{name}-%{version}-filelist


%clean
rm -rf %{buildroot}

%files -f %{name}-%{version}-filelist
%defattr(-,root,root,-)
%dir %{_nseventsdir}/%{name}-update
%doc

%changelog

